<?php

namespace App\Services;

/** Computes direct-child formulas without mixing units or counting a node twice. */
class KontrakCalculator
{
    public const TYPES = ['manual', 'sum', 'ratio', 'weighted_sum', 'scale', 'offset_ratio'];

    public function calculateTree($root, array $actuals = []): array
    {
        $results = [];
        $this->visit($root, $actuals, $results, null, []);

        return $results;
    }

    /** Only manual sources used by the selected formula need realization input. */
    public function inputNodes($node, array $results): array
    {
        $result = $results[(string) $node->id];
        if ($result['type'] === 'manual') {
            return [$node];
        }
        $inputs = [];
        foreach ($node->childrenRecursive as $child) {
            if (in_array((string) $child->id, $result['source_ids'], true)) {
                foreach ($this->inputNodes($child, $results) as $input) {
                    $inputs[(string) $input->id] = $input;
                }
            }
        }

        return array_values($inputs);
    }

    private function sourceIds(string $type, array $config, array $children): array
    {
        $ids = match ($type) {
            'sum' => $config['child_ids'] ?? array_keys($children),
            'weighted_sum' => array_keys(array_filter($config['weights'] ?? [], fn ($weight) => (float) $weight !== 0.0)),
            'scale' => [$config['source_id'] ?? null],
            'ratio', 'offset_ratio' => [$config['numerator_id'] ?? null, $config['denominator_id'] ?? null],
            default => [],
        };

        return array_values(array_unique(array_map('strval', array_filter($ids, fn ($id) => $id !== null))));
    }

    private function visit($node, array $actuals, array &$results, ?string $ikk, array $path): array
    {
        $code = strtoupper(trim((string) $node->kode));
        if (($node->jenis ?? 'PK') === 'PK' && preg_match('/^IKK\s+\d+$/', $code)) {
            $ikk = $code;
            $path = [];
        }
        $children = [];
        foreach ($node->childrenRecursive as $child) {
            $childPath = [...$path, strtoupper(trim((string) $child->kode))];
            $children[(string) $child->id] = $this->visit($child, $actuals, $results, $ikk, $childPath);
        }
        $preset = $this->preset($node, $ikk, $path);
        $explicit = $node->calculation_type !== null;
        $type = $explicit ? $node->calculation_type : $preset['type'];
        $config = $explicit ? ($node->calculation_config ?? []) : $preset['config'];
        [$target, $error] = $this->evaluate($type, $config, $children, 'target', $node->target);
        [$actual, $actualError] = $this->evaluate($type, $config, $children, 'actual', $actuals[(string) $node->id] ?? null);
        $unit = $node->jenis_target;
        if (! $unit && $ikk !== null && ! $path) {
            $unit = in_array($ikk, ['IKK 15', 'IKK 22', 'IKK 25', 'IKK 26'], true) ? 'nilai' : 'persen';
        }
        $result = [
            'target' => $target, 'actual' => $actual,
            'error' => $error, 'actual_error' => $actualError,
            'type' => $type, 'config' => $config, 'preset' => ! $explicit,
            'source_ids' => $this->sourceIds($type, $config, $children),
            'unit' => $unit,
        ];

        return $results[(string) $node->id] = $result;
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
