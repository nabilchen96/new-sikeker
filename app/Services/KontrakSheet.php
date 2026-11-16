<?php

namespace App\Services;

use App\Models\Kontrak;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class KontrakSheet
{
    public function rows(Kontrak $root): array
    {
        $results = (new KontrakCalculator)->calculateTree($root);
        $rows = [];
        $nodes = [];
        $walk = function ($node, $parent = null) use (&$walk, &$rows, &$nodes, $results) {
            $rows[] = ['key' => 'id-'.$node->id, 'id' => $node->id, 'parent_key' => $parent, 'kode' => (string) $node->kode, 'nama' => $node->nama, 'jenis_target' => $results[(string) $node->id]['unit'] ?? '', 'input' => ''];
            $nodes[] = $node;
            foreach ($node->childrenRecursive->sort(fn ($a, $b) => strnatcasecmp((string) $a->kode, (string) $b->kode)) as $child) {
                $walk($child, 'id-'.$node->id);
            }
        };
        $walk($root);
        $numbers = [];
        foreach ($nodes as $index => $node) {
            $numbers[(string) $node->id] = $index + 1;
        }
        foreach ($nodes as $index => $node) {
            $rows[$index]['input'] = $this->input($node, $results[(string) $node->id], $numbers);
        }

        return $rows;
    }

    private function input($node, array $result, array $numbers): string
    {
        $c = $result['config'];
        $ref = fn ($id) => $id !== null && isset($numbers[(string) $id]) ? 'E'.$numbers[(string) $id] : '#REF!';
        $num = fn ($value) => (string) (float) $value;
        switch ($result['type']) {
            case 'manual':
                $raw = (string) ($node->target ?? '');

                return str_contains($raw, '.') ? rtrim(rtrim($raw, '0'), '.') : $raw;
            case 'sum':
                $ids = $c['child_ids'] ?? $node->childrenRecursive->pluck('id')->all();

                return $ids ? '='.implode('+', array_map($ref, $ids)) : '=SUM()';
            case 'ratio': case 'offset_ratio':
                $ratio = $ref($c['numerator_id'] ?? null).'/'.$ref($c['denominator_id'] ?? null).'*'.$num($c['factor'] ?? 100);

                return '='.($result['type'] === 'offset_ratio' && ($c['baseline'] ?? 0) != 0 ? $num($c['baseline']).'+' : '').$ratio;
            case 'scale':
                $factor = (float) ($c['factor'] ?? 1);
                $text = $factor > 0 && $factor < 1 && abs($factor * 100 - round($factor * 100)) < 0.00000001 ? $num($factor * 100).'%' : $num($factor);

                return '='.$ref($c['source_id'] ?? null).'*'.$text;
            case 'weighted_sum':
                $terms = [];
                foreach ($c['weights'] ?? [] as $id => $weight) {
                    if ((float) $weight !== 0.0) {
                        $terms[] = $ref($id).'*'.$num($weight).'%';
                    }
                }

                return $terms ? '='.implode('+', $terms) : '=SUM()';
            case 'formula': return isset($c['ast']) ? (new KontrakFormula)->render($c['ast'], $numbers) : '=';
        }

        return '';
    }

    public function compile(array $rows): array
    {
        $keys = [];
        $nodes = [];
        $links = [];
        foreach ($rows as $index => $row) {
            $key = $row['key'];
            if (isset($keys[$key])) {
                $this->fail($index, 'Baris tidak boleh dipakai dua kali.');
            }
            $keys[$key] = true;
            $nodes[$key] = ['key' => $key, 'id' => $row['id'] ?? null, 'kode' => $row['kode'] ?? '', 'nama' => $row['nama'] ?? '', 'jenis_target' => $row['jenis_target'] ?? null, 'target' => null, 'calculation_type' => 'manual', 'calculation_config' => [], 'children' => []];
            $links[$key] = $row['parent_key'] ?? null;
        }
        $allKeys = array_map('strval', array_keys($keys));
        foreach (array_values($rows) as $index => $row) {
            $key = $row['key'];
            $raw = trim((string) ($row['input'] ?? ''));
            if (str_starts_with($raw, '=')) {
                try {
                    $ast = (new KontrakFormula)->parse($raw, $allKeys);
                    (new KontrakFormula)->validate($ast, $allKeys);
                } catch (\DomainException $error) {
                    $this->fail($index, $error->getMessage());
                }
                $nodes[$key]['calculation_type'] = 'formula';
                $nodes[$key]['calculation_config'] = ['ast' => $ast];
            } elseif ($raw !== '') {
                $raw = str_replace(',', '.', $raw);
                if (! is_numeric($raw) || ! is_finite((float) $raw) || $raw < 0) {
                    $this->fail($index, 'Isi angka positif/nol atau rumus yang diawali =.');
                }
                $nodes[$key]['target'] = $raw;
            }
        }
        $first = $allKeys[0];
        if ($links[$first] !== null && $links[$first] !== '') {
            $this->fail(0, 'Baris pertama adalah induk indikator.');
        }
        $children = [];
        foreach ($allKeys as $index => $key) {
            if ($index === 0) {
                continue;
            }
            $parent = $links[$key];
            if (! is_string($parent) || ! isset($nodes[$parent]) || $parent === $key) {
                $this->fail($index, 'Pilih induk yang benar pada kolom C.');
            }
            $children[$parent][] = $key;
        }
        $built = [];
        $visiting = [];
        $build = function ($key) use (&$build, &$built, &$visiting, $nodes, $children) {
            if (isset($visiting[$key])) {
                throw ValidationException::withMessages(['rows' => 'Susunan induk membentuk lingkaran.']);
            }
            $visiting[$key] = true;
            $node = $nodes[$key];
            foreach ($children[$key] ?? [] as $child) {
                $node['children'][] = $build($child);
            }
            unset($visiting[$key]);
            $built[$key] = true;

            return $node;
        };
        $tree = $build($first);
        if (count($built) !== count($nodes)) {
            throw ValidationException::withMessages(['rows' => 'Semua baris harus berada di bawah indikator pertama.']);
        }
        $done = [];
        $active = [];
        $check = function ($key) use (&$check, &$done, &$active, $nodes, $allKeys) {
            if (isset($done[$key])) {
                return;
            }
            if (isset($active[$key])) {
                $this->fail(array_search($key, $allKeys, true), 'Rumus membentuk lingkaran; sel tidak boleh bergantung pada dirinya sendiri.');
            }
            $active[$key] = true;
            if ($nodes[$key]['calculation_type'] === 'formula') {
                foreach ((new KontrakFormula)->references($nodes[$key]['calculation_config']['ast']) as $source) {
                    $check($source);
                }
            }
            unset($active[$key]);
            $done[$key] = true;
        };
        foreach ($allKeys as $key) {
            $check($key);
        }

        return $tree;
    }

    public function preview(array $rows, string $jenis): array
    {
        $tree = $this->compile($rows);
        $ids = [];
        foreach (array_values($rows) as $index => $row) {
            $ids[$row['key']] = (string) ($index + 1);
        }
        $make = function ($data) use (&$make, $ids, $jenis) {
            $config = $data['calculation_config'];
            if (isset($config['ast'])) {
                $config['ast'] = (new KontrakFormula)->mapReferences($config['ast'], fn ($key) => $ids[$key]);
            }
            $node = new Kontrak;
            $node->forceFill(['id' => (int) $ids[$data['key']], 'kode' => $data['kode'], 'nama' => $data['nama'], 'jenis' => $jenis, 'target' => $data['target'], 'jenis_target' => $data['jenis_target'], 'calculation_type' => $data['calculation_type'], 'calculation_config' => $config]);
            $node->setRelation('childrenRecursive', new Collection(array_map($make, $data['children'])));

            return $node;
        };
        $results = (new KontrakCalculator)->calculateTree($make($tree));

        return array_map(fn ($row) => ['key' => $row['key'], 'value' => $results[$ids[$row['key']]]['target'], 'error' => $results[$ids[$row['key']]]['error']], array_values($rows));
    }

    private function fail(int $row, string $message): never
    {
        throw ValidationException::withMessages(['rows.'.$row.'.input' => 'E'.($row + 1).': '.$message]);
    }
}
