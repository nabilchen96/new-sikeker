<?php

namespace App\Services;

/** Computes direct-child formulas without mixing units or counting a node twice. */
class KontrakCalculator
{
    public const TYPES = ['manual', 'sum', 'ratio', 'weighted_sum', 'scale', 'offset_ratio', 'formula'];

    public function calculateTree($root, array $actuals = []): array
    {
        $metadata = [];
        $this->prepare($root, $metadata, null, []);
        $results = [];
        $visiting = [];
        $resolve = function (string $id) use (&$resolve, &$results, &$visiting, $metadata, $actuals): array {
            if (isset($results[$id])) {
                return $results[$id];
            }
            if (! isset($metadata[$id])) {
                throw new \DomainException('Sumber rumus tidak ditemukan.');
            }
            $item = $metadata[$id];
            $base = $item['result'];
            if (isset($visiting[$id])) {
                return $base + ['target' => null, 'actual' => null, 'error' => 'Rumus membentuk siklus.', 'actual_error' => 'Rumus membentuk siklus.'];
            }
            $visiting[$id] = true;
            foreach ($base['source_ids'] as $sourceId) {
                if (isset($metadata[$sourceId])) {
                    $resolve($sourceId);
                }
            }
            $children = [];
            foreach ($item['children'] as $childId) {
                $children[$childId] = $results[$childId] ?? ['target' => null, 'actual' => null];
            }
            if ($base['type'] === 'formula') {
                $values = [];
                foreach (['target', 'actual'] as $metric) {
                    try {
                        if (! is_array($base['config']['ast'] ?? null)) {
                            throw new \DomainException('Isi rumus melalui tabel Excel.');
                        }
                        $value = (new KontrakFormula)->evaluate($base['config']['ast'], function ($source) use (&$resolve, $metric) {
                            $result = $resolve($source);
                            if ($result[$metric] === null) {
                                throw new \DomainException('Nilai sumber belum lengkap atau rumus sumber belum valid.');
                            }

                            return $result[$metric];
                        });
                        $values[$metric] = [$value, null];
                    } catch (\DomainException $error) {
                        $values[$metric] = [null, $error->getMessage()];
                    }
                }
                [$target, $error] = $values['target'];
                [$actual, $actualError] = $values['actual'];
            } else {
                [$target, $error] = $this->evaluate($base['type'], $base['config'], $children, 'target', $item['node']->target);
                [$actual, $actualError] = $this->evaluate($base['type'], $base['config'], $children, 'actual', $actuals[$id] ?? null);
            }
            unset($visiting[$id]);

            return $results[$id] = $base + ['target' => $target, 'actual' => $actual, 'error' => $error, 'actual_error' => $actualError];
        };
        foreach (array_keys($metadata) as $id) {
            $resolve((string) $id);
        }

        return $results;
    }

    /** Resolve all manual inputs used by the dependency graph, including sibling references. */
    public function inputNodes($node, array $results): array
    {
        $nodes = [];
        $collect = function ($item) use (&$collect, &$nodes) {
            $nodes[(string) $item->id] = $item;
            foreach ($item->childrenRecursive as $child) {
                $collect($child);
            }
        };
        $collect($node);
        $inputs = [];
        $visited = [];
        $walk = function (string $id) use (&$walk, &$inputs, &$visited, $nodes, $results) {
            if (isset($visited[$id]) || ! isset($nodes[$id], $results[$id])) {
                return;
            }
            $visited[$id] = true;
            if ($results[$id]['type'] === 'manual') {
                $inputs[$id] = $nodes[$id];

                return;
            }
            foreach ($results[$id]['source_ids'] as $source) {
                $walk($source);
            }
        };
        $walk((string) $node->id);

        return array_values($inputs);
    }

    private function sourceIds(string $type, array $config, array $children): array
    {
        $ids = match ($type) {
            'formula' => isset($config['ast']) ? (new KontrakFormula)->references($config['ast']) : [],
            'sum' => $config['child_ids'] ?? array_keys($children),
            'weighted_sum' => array_keys(array_filter($config['weights'] ?? [], fn ($weight) => (float) $weight !== 0.0)),
            'scale' => [$config['source_id'] ?? null],
            'ratio', 'offset_ratio' => [$config['numerator_id'] ?? null, $config['denominator_id'] ?? null],
            default => [],
        };

        return array_values(array_unique(array_map('strval', array_filter($ids, fn ($id) => $id !== null))));
    }

    private function prepare($node, array &$metadata, ?string $ikk, array $path): void
    {
        $code = strtoupper(trim((string) $node->kode));
        if (($node->jenis ?? 'PK') === 'PK' && preg_match('/^IKK\s+\d+$/', $code)) {
            $ikk = $code;
            $path = [];
        }
        $children = [];
        foreach ($node->childrenRecursive as $child) {
            $children[(string) $child->id] = true;
            $this->prepare($child, $metadata, $ikk, [...$path, strtoupper(trim((string) $child->kode))]);
        }
        $preset = $this->preset($node, $ikk, $path);
        $explicit = $node->calculation_type !== null;
        $type = $explicit ? $node->calculation_type : $preset['type'];
        $config = $explicit ? ($node->calculation_config ?? []) : $preset['config'];
        $unit = $node->jenis_target;
        if (! $unit && $ikk !== null && ! $path) {
            $unit = in_array($ikk, ['IKK 15', 'IKK 22', 'IKK 25', 'IKK 26'], true) ? 'nilai' : 'persen';
        }
        $metadata[(string) $node->id] = [
            'node' => $node, 'children' => array_keys($children),
            'result' => ['type' => $type, 'config' => $config, 'preset' => ! $explicit, 'unit' => $unit, 'source_ids' => $this->sourceIds($type, $config, $children)],
        ];
    }

    public function evaluate(string $type, array $config, array $children, string $metric, $manual): array
    {
        if ($type === 'manual') {
            return [is_numeric($manual) ? (float) $manual : null, is_numeric($manual) ? null : 'Nilai belum diisi.'];
        }
        $read = function ($id) use ($children, $metric) {
            if ($id === null || ! isset($children[(string) $id])) {
                throw new \DomainException('Pilih sumber dari anak langsung indikator ini.');
            }
            $value = $children[(string) $id][$metric];
            if ($value === null) {
                throw new \DomainException('Nilai sumber belum lengkap atau rumus sumber belum valid.');
            }

            return $value;
        };
        try {
            if ($type === 'sum') {
                $ids = $config['child_ids'] ?? array_keys($children);
                if (! $ids) {
                    throw new \DomainException('Tambahkan anak sebagai sumber penjumlahan.');
                }
                $value = 0;
                foreach ($ids as $id) {
                    $value += $read($id);
                }
            } elseif ($type === 'weighted_sum') {
                if (! $children) {
                    throw new \DomainException('Tambahkan anak, lalu isi bobotnya.');
                }
                if (empty($config['weights']) || array_sum($config['weights']) <= 0) {
                    throw new \DomainException('Isi bobot untuk anak indikator.');
                }
                if (array_diff(array_keys($children), array_keys($config['weights']))) {
                    throw new \DomainException('Ada anak yang belum diberi bobot. Isi bobot atau 0 untuk mengabaikannya.');
                }
                $value = 0;
                foreach ($config['weights'] as $id => $weight) {
                    if ((float) $weight !== 0.0) {
                        $value += $read($id) * (float) $weight / 100;
                    }
                }
            } elseif ($type === 'scale') {
                $value = $read($config['source_id'] ?? null) * (float) ($config['factor'] ?? 1);
            } elseif (in_array($type, ['ratio', 'offset_ratio'], true)) {
                $denominator = $read($config['denominator_id'] ?? null);
                if ($denominator == 0) {
                    throw new \DomainException('Pembagi nol; hasil belum dapat dihitung.');
                }
                $value = $read($config['numerator_id'] ?? null) / $denominator * (float) ($config['factor'] ?? 100);
                if ($type === 'offset_ratio') {
                    $value += (float) ($config['baseline'] ?? 0);
                }
            } else {
                throw new \DomainException('Metode perhitungan tidak dikenal.');
            }

            return is_finite($value) ? [$value, null] : [null, 'Hasil di luar rentang angka.'];
        } catch (\DomainException $error) {
            return [null, $error->getMessage()];
        }
    }

    /** Legacy data gets an Excel profile; explicit saved settings always win. */
    private function preset($node, ?string $ikk, array $path): array
    {
        $children = [];
        foreach ($node->childrenRecursive as $child) {
            $children[strtoupper(trim((string) $child->kode))] = (string) $child->id;
        }
        $make = fn ($type, $config = []) => ['type' => $type, 'config' => $config];
        $ratio = fn ($a, $b, $factor = 100) => $make('ratio', [
            'numerator_id' => $children[$a] ?? null,
            'denominator_id' => $children[$b] ?? null, 'factor' => $factor,
        ]);
        $selectedSum = fn ($codes) => $make('sum', ['child_ids' => array_map(fn ($code) => $children[$code] ?? null, $codes)]);
        if (! $children) {
            return $make('manual');
        }
        if ($ikk !== null && ! $path) {
            if (in_array($ikk, ['IKK 15', 'IKK 22', 'IKK 25', 'IKK 26'], true)) {
                return $make('manual');
            }
            if (in_array($ikk, ['IKK 4', 'IKK 5', 'IKK 16'], true)) {
                return $selectedSum(['A', 'B']);
            }
            if ($ikk === 'IKK 11') {
                return $selectedSum(['1', '2', '3']);
            }
            if ($ikk === 'IKK 14') {
                $weights = [];
                foreach (['1' => 40, '2' => 40, '3' => 20] as $code => $weight) {
                    // Missing expected sources remain visible as an invalid formula.
                    $weights[$children[$code] ?? "missing-$code"] = $weight;
                }

                return $make('weighted_sum', ['weights' => $weights]);
            }
            if ($ikk === 'IKK 24') {
                return $make('offset_ratio', [
                    'numerator_id' => $children['3'] ?? null,
                    'denominator_id' => $children['4'] ?? null,
                    'factor' => 50, 'baseline' => 50,
                ]);
            }

            return isset($children['A']) || isset($children['B']) ? $ratio('A', 'B') : $ratio('1', '2');
        }
        if ($ikk === 'IKK 4' && $path === ['A']) {
            return $make('scale', ['source_id' => $children['1'] ?? (count($children) === 1 ? reset($children) : null), 'factor' => 60]);
        }
        if ($ikk === 'IKK 4' && $path === ['B']) {
            return $ratio('1', '2', 40);
        }
        if ($ikk === 'IKK 5' && count($path) === 1 && in_array($path[0], ['A', 'B'], true)) {
            return $make('scale', ['source_id' => $children['1'] ?? null, 'factor' => $path[0] === 'A' ? 0.4 : 0.6]);
        }
        if ($ikk === 'IKK 16' && count($path) === 1 && in_array($path[0], ['A', 'B'], true)) {
            return $ratio('1', '2', $path[0] === 'A' ? 60 : 40);
        }
        // KPI targets are declared values, even when explanatory children exist.
        if (($node->jenis ?? 'PK') === 'KPI' && $ikk === null) {
            return $make('manual');
        }

        return $make('sum');
    }
}
