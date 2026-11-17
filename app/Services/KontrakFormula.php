<?php

namespace App\Services;

/** Small arithmetic language: cell references, + - * / %, parentheses and SUM. No eval. */
class KontrakFormula
{
    private array $tokens = [];

    private int $position = 0;

    private array $rows = [];

    public function parse(string $formula, array $rowKeys): array
    {
        $this->tokens = [];
        $this->position = 0;
        $this->rows = array_values($rowKeys);
        $source = trim($formula);
        if (! str_starts_with($source, '=') || strlen($source) > 4000) {
            throw new \DomainException('Rumus harus diawali = dan maksimal 4000 karakter.');
        }
        $source = substr($source, 1);
        $offset = 0;
        while ($offset < strlen($source)) {
            if (preg_match('/\G\s+/A', $source, $match, 0, $offset)) {
                $offset += strlen($match[0]);

                continue;
            }
            if (! preg_match('/\G(\$?E\$?[1-9][0-9]*|(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?|SUM|[+\-*\/()%:;,])/Ai', $source, $match, 0, $offset)) {
                throw new \DomainException('Gunakan referensi kolom E, angka, + - * / %, kurung, atau SUM. Desimal rumus memakai titik.');
            }
            $this->tokens[] = strtoupper($match[0]);
            $offset += strlen($match[0]);
            if (count($this->tokens) > 1000) {
                throw new \DomainException('Rumus terlalu panjang.');
            }
        }
        $ast = $this->expression(0);
        if ($this->peek() !== null) {
            throw new \DomainException('Ada bagian rumus yang belum benar.');
        }

        return $ast;
    }

    private function expression(int $depth): array
    {
        $left = $this->term($depth + 1);
        while (in_array($this->peek(), ['+', '-'], true)) {
            $operator = $this->next();
            $left = ['op' => $operator === '+' ? 'add' : 'sub', 'left' => $left, 'right' => $this->term($depth + 1)];
        }

        return $left;
    }

    private function term(int $depth): array
    {
        $left = $this->atom($depth + 1);
        while (in_array($this->peek(), ['*', '/'], true)) {
            $operator = $this->next();
            $left = ['op' => $operator === '*' ? 'mul' : 'div', 'left' => $left, 'right' => $this->atom($depth + 1)];
        }

        return $left;
    }

    private function atom(int $depth): array
    {
        if ($depth > 40) {
            throw new \DomainException('Kurung rumus terlalu dalam.');
        }
        $token = $this->next();
        if ($token === '+' || $token === '-') {
            $value = ['op' => $token === '-' ? 'neg' : 'pos', 'value' => $this->atom($depth + 1)];
        } elseif ($token === '(') {
            $value = $this->expression($depth + 1);
            $this->expect(')');
        } elseif ($token === 'SUM') {
            $this->expect('(');
            $items = [];
            do {
                if (preg_match('/^\$?E\$?([1-9][0-9]*)$/', $this->peek() ?? '', $a) && ($this->tokens[$this->position + 1] ?? null) === ':') {
                    $this->next();
                    $this->next();
                    if (! preg_match('/^\$?E\$?([1-9][0-9]*)$/', $this->next() ?? '', $b)) {
                        throw new \DomainException('Rentang SUM harus seperti E2:E4.');
                    }
                    if ((int) $b[1] < (int) $a[1] || (int) $b[1] > count($this->rows)) {
                        throw new \DomainException('Rentang SUM berada di luar tabel.');
                    }
                    for ($row = (int) $a[1]; $row <= (int) $b[1]; $row++) {
                        $items[] = $this->reference($row);
                    }
                } else {
                    $items[] = $this->expression($depth + 1);
                }
                if (! in_array($this->peek(), [';', ','], true)) {
                    break;
                }
                $this->next();
            } while (true);
            $this->expect(')');
            $value = ['op' => 'sum', 'items' => $items];
        } elseif (preg_match('/^\$?E\$?([1-9][0-9]*)$/', $token ?? '', $match)) {
            $value = $this->reference((int) $match[1]);
        } elseif ($token !== null && is_numeric($token) && is_finite((float) $token)) {
            $value = ['op' => 'number', 'value' => (float) $token];
        } else {
            throw new \DomainException('Rumus belum lengkap.');
        }
        while ($this->peek() === '%') {
            $this->next();
            $value = ['op' => 'percent', 'value' => $value];
        }

        return $value;
    }

    private function reference(int $row): array
    {
        if (! isset($this->rows[$row - 1])) {
            throw new \DomainException("Sel E$row berada di luar tabel.");
        }

        return ['op' => 'ref', 'id' => (string) $this->rows[$row - 1]];
    }

    private function peek(): ?string
    {
        return $this->tokens[$this->position] ?? null;
    }

    private function next(): ?string
    {
        return $this->tokens[$this->position++] ?? null;
    }

    private function expect(string $token): void
    {
        if ($this->next() !== $token) {
            throw new \DomainException("Rumus memerlukan $token.");
        }
    }

    public function validate(array $ast, array $allowedIds): void
    {
        $count = 0;
        $walk = function (array $node, int $depth) use (&$walk, &$count, $allowedIds) {
            if ($depth > 80 || ++$count > 1000) {
                throw new \DomainException('Rumus terlalu panjang.');
            }
            switch ($node['op'] ?? '') {
                case 'number': if (! is_numeric($node['value'] ?? null) || ! is_finite((float) $node['value'])) {
                    throw new \DomainException('Angka rumus tidak valid.');
                } break;
                case 'ref': if (! is_string($node['id'] ?? null) || ! in_array($node['id'], $allowedIds, true)) {
                    throw new \DomainException('Referensi rumus harus berada dalam tabel indikator ini.');
                } break;
                case 'add': case 'sub': case 'mul': case 'div':
                    if (! is_array($node['left'] ?? null) || ! is_array($node['right'] ?? null)) {
                        throw new \DomainException('Operasi rumus tidak valid.');
                    }
                    $walk($node['left'], $depth + 1);
                    $walk($node['right'], $depth + 1);
                    break;
                case 'neg': case 'pos': case 'percent':
                    if (! is_array($node['value'] ?? null)) {
                        throw new \DomainException('Operasi rumus tidak valid.');
                    } $walk($node['value'], $depth + 1);
                    break;
                case 'sum':
                    if (! is_array($node['items'] ?? null) || ! $node['items']) {
                        throw new \DomainException('SUM memerlukan sumber.');
                    }
                    foreach ($node['items'] as $item) {
                        if (! is_array($item)) {
                            throw new \DomainException('Sumber SUM tidak valid.');
                        } $walk($item, $depth + 1);
                    } break;
                default: throw new \DomainException('Operasi rumus tidak didukung.');
            }
        };
        $walk($ast, 0);
    }

    public function references(array $ast): array
    {
        $refs = [];
        $this->mapReferences($ast, function ($id) use (&$refs) {
            $refs[] = (string) $id;

            return (string) $id;
        });

        return array_values(array_unique($refs));
    }

    public function mapReferences(array $ast, callable $map): array
    {
        if (($ast['op'] ?? '') === 'ref') {
            $ast['id'] = $map($ast['id']);
        }
        foreach (['left', 'right', 'value'] as $field) {
            if (isset($ast[$field]) && is_array($ast[$field])) {
                $ast[$field] = $this->mapReferences($ast[$field], $map);
            }
        }
        if (isset($ast['items'])) {
            $ast['items'] = array_map(fn ($item) => $this->mapReferences($item, $map), $ast['items']);
        }

        return $ast;
    }

    public function evaluate(array $ast, callable $read, int $depth = 0): float
    {
        if ($depth > 80) {
            throw new \DomainException('Rumus terlalu dalam.');
        }
        $eval = fn ($node) => $this->evaluate($node, $read, $depth + 1);
        $value = match ($ast['op'] ?? '') {
            'number' => (float) $ast['value'], 'ref' => $read((string) $ast['id']),
            'add' => $eval($ast['left']) + $eval($ast['right']), 'sub' => $eval($ast['left']) - $eval($ast['right']),
            'mul' => $eval($ast['left']) * $eval($ast['right']), 'div' => $this->divide($eval($ast['left']), $eval($ast['right'])),
            'neg' => -$eval($ast['value']), 'pos' => $eval($ast['value']), 'percent' => $eval($ast['value']) / 100,
            'sum' => array_sum(array_map($eval, $ast['items'])),
            default => throw new \DomainException('Operasi rumus tidak didukung.'),
        };
        if (! is_finite($value)) {
            throw new \DomainException('Hasil di luar rentang angka.');
        }

        return $value;
    }

    /** Progress uses actual numerators against the planned reference denominator. */
    public function evaluateProgress(array $ast, callable $readActual, callable $readTarget): float
    {
        $freeze = function (array $node, int $depth = 0) use (&$freeze, $readTarget): array {
            if ($depth > 80) {
                throw new \DomainException('Rumus terlalu dalam.');
            }
            if (($node['op'] ?? '') === 'div') {
                $node['left'] = $freeze($node['left'], $depth + 1);
                $node['right'] = ['op' => 'number', 'value' => $this->evaluate($node['right'], $readTarget)];
            } else {
                foreach (['left', 'right', 'value'] as $field) {
                    if (isset($node[$field]) && is_array($node[$field])) {
                        $node[$field] = $freeze($node[$field], $depth + 1);
                    }
                }
                if (isset($node['items'])) {
                    $node['items'] = array_map(fn ($item) => $freeze($item, $depth + 1), $node['items']);
                }
            }

            return $node;
        };

        return $this->evaluate($freeze($ast), $readActual);
    }

    private function divide(float $a, float $b): float
    {
        if ($b == 0) {
            throw new \DomainException('Pembagi nol; hasil belum dapat dihitung.');
        }

        return $a / $b;
    }

    public function render(array $ast, array $rowNumbers): string
    {
        $render = function ($node) use (&$render, $rowNumbers): array {
            $op = $node['op'] ?? '';
            if ($op === 'number') {
                return [(string) $node['value'], 100];
            }
            if ($op === 'ref') {
                return [isset($rowNumbers[(string) $node['id']]) ? 'E'.$rowNumbers[(string) $node['id']] : '#REF!', 100];
            }
            if ($op === 'sum') {
                return ['SUM('.implode(';', array_map(fn ($item) => $render($item)[0], $node['items'])).')', 100];
            }
            if (in_array($op, ['neg', 'pos', 'percent'], true)) {
                [$text, $level] = $render($node['value']);
                $precedence = $op === 'percent' ? 90 : 80;
                if ($level < $precedence) {
                    $text = '('.$text.')';
                }

                return [$op === 'percent' ? $text.'%' : ($op === 'neg' ? '-' : '+').$text, $precedence];
            }
            $operator = ['add' => '+', 'sub' => '-', 'mul' => '*', 'div' => '/'][$op] ?? null;
            if ($operator === null) {
                return ['#REF!', 100];
            }
            $precedence = in_array($op, ['add', 'sub'], true) ? 50 : 60;
            [$left, $leftLevel] = $render($node['left']);
            [$right, $rightLevel] = $render($node['right']);
            if ($leftLevel < $precedence) {
                $left = '('.$left.')';
            }
            if ($rightLevel <= $precedence) {
                $right = '('.$right.')';
            }

            return [$left.$operator.$right, $precedence];
        };

        return '='.$render($ast)[0];
    }
}
