<?php

namespace App\Services;

use App\Models\Kontrak;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Saves an indicator and its complete editor subtree atomically, without deleting data. */
class KontrakTreeWriter
{
    private array $keys = [];

    private array $ids = [];

    private array $saved = [];

    private int $count = 0;

    public function save(int $parentId, string $jenis, array $tree): Kontrak
    {
        $this->keys = $this->ids = $this->saved = [];
        $this->count = 0;
        $tree = $this->validateNode($tree, 'root', 0);

        return DB::transaction(function () use ($parentId, $jenis, $tree) {
            $parent = Kontrak::whereKey($parentId)->where('jenis', $jenis)->lockForUpdate()->first();
            if (! $parent || ! DB::table('tahuns')->where('id', $parent->id_tahun)->where('status', 'Aktif')->exists()) {
                $this->fail('id_parent', 'Induk harus berasal dari jenis dan tahun aktif yang sama.');
            }
            $root = $this->persist($tree, $parent);
            foreach ($this->saved as [$model, $data]) {
                $config = $data['calculation_config'];
                foreach (['source_id', 'numerator_id', 'denominator_id'] as $field) {
                    if (array_key_exists($field, $config)) {
                        $config[$field] = isset($this->keys[$config[$field] ?? '']) ? $this->keys[$config[$field]] : null;
                    }
                }
                if (isset($config['weights'])) {
                    $weights = [];
                    foreach ($config['weights'] as $key => $weight) {
                        $weights[(string) $this->keys[$key]] = $weight;
                    }
                    $config['weights'] = $weights;
                }
                if (isset($config['child_ids'])) {
                    $config['child_ids'] = array_map(fn ($key) => $this->keys[$key], $config['child_ids']);
                }
                $model->update(['calculation_config' => $config]);
            }

            return $root->fresh('childrenRecursive');
        });
    }

    private function validateNode(array $node, string $path, int $depth): array
    {
        if ($depth > 6 || ++$this->count > 200) {
            $this->fail($path, 'Maksimal 200 baris dan 7 tingkat indikator dalam satu pengisian.');
        }
        $node['target'] = is_string($node['target'] ?? null) ? str_replace(',', '.', $node['target']) : ($node['target'] ?? null);
        if ($node['target'] === '') {
            $node['target'] = null;
        }
        $validator = Validator::make($node, [
            'key' => ['required', 'string', 'max:80'],
            'id' => ['nullable', 'integer', 'min:1'],
            'kode' => ['required', 'string', 'max:255'],
            'nama' => ['required', 'string', 'max:5000'],
            'target' => ['nullable', 'numeric', 'decimal:0,6', 'min:0', 'max:9999999999999999'],
            'jenis_target' => ['nullable', 'string', 'max:255'],
            'calculation_type' => ['required', Rule::in(KontrakCalculator::TYPES)],
            'calculation_config' => ['present', 'array'],
            'children' => ['present', 'array', 'max:50'],
            'children.*' => ['array'],
        ], [
            'nama.required' => 'Isi nama indikator atau rincian.',
            'kode.required' => 'Isi kode atau urutan rincian.',
            'target.numeric' => 'Target harus berupa angka, misalnya 80,95.',
            'target.decimal' => 'Target maksimal memiliki 6 angka desimal.',
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages(collect($validator->errors()->messages())->mapWithKeys(fn ($messages, $field) => ["$path.$field" => $messages])->all());
        }
        if (isset($this->keys[$node['key']]) || (! empty($node['id']) && isset($this->ids[$node['id']]))) {
            $this->fail($path, 'Baris indikator tidak boleh dipakai dua kali.');
        }
        $this->keys[$node['key']] = true;
        if (! empty($node['id'])) {
            $this->ids[$node['id']] = true;
        }
        $children = [];
        foreach ($node['children'] as $index => $child) {
            $children[] = $this->validateNode($child, "$path.children.$index", $depth + 1);
        }
        $node['children'] = $children;
        $childKeys = array_column($children, 'key');
        $type = $node['calculation_type'];
        $config = $node['calculation_config'];
        $clean = [];
        $references = match ($type) {
            'ratio', 'offset_ratio' => ['numerator_id', 'denominator_id'],
            'scale' => ['source_id'],
            default => [],
        };
        foreach ($references as $field) {
            $reference = $config[$field] ?? null;
            if ($reference !== null && $reference !== '' && (! is_string($reference) || ! in_array($reference, $childKeys, true))) {
                $this->fail("$path.calculation_config.$field", 'Sumber rumus harus anak langsung pada bagian ini.');
            }
            $clean[$field] = $reference === '' ? null : $reference;
        }
        if (! empty($clean['numerator_id']) && $clean['numerator_id'] === $clean['denominator_id']) {
            $this->fail($path, 'Pembilang dan pembagi harus berbeda.');
        }
        if ($references) {
            $clean['factor'] = $this->number($config['factor'] ?? ($type === 'scale' ? 1 : 100), 1000000, "$path.calculation_config.factor");
        }
        if ($type === 'offset_ratio') {
            $clean['baseline'] = $this->number($config['baseline'] ?? 0, 1000000, "$path.calculation_config.baseline");
        }
        if ($type === 'weighted_sum') {
            $weights = $config['weights'] ?? [];
            if (! is_array($weights)) {
                $this->fail($path, 'Bobot tidak valid.');
            }
            $clean['weights'] = [];
            foreach ($weights as $key => $weight) {
                if (! in_array((string) $key, $childKeys, true)) {
                    $this->fail($path, 'Bobot harus berasal dari anak langsung pada bagian ini.');
                }
                $clean['weights'][$key] = $this->number($weight === '' || $weight === null ? 0 : $weight, 100, "$path.calculation_config.weights.$key");
            }
        }
        if ($type === 'sum' && isset($config['child_ids'])) {
            if (! is_array($config['child_ids'])) {
                $this->fail($path, 'Sumber penjumlahan tidak valid.');
            }
            foreach ($config['child_ids'] as $reference) {
                if (! is_string($reference) || ! in_array($reference, $childKeys, true)) {
                    $this->fail($path, 'Sumber penjumlahan harus anak langsung pada bagian ini.');
                }
            }
            $clean['child_ids'] = array_values(array_unique($config['child_ids']));
        }
        $node['calculation_config'] = $clean;

        return $node;
    }

    private function number($value, int $max, string $path): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }
        if (! is_numeric($value) || ! is_finite((float) $value) || $value < 0 || $value > $max) {
            $this->fail($path, "Isi angka dari 0 sampai $max.");
        }

        return (float) $value;
    }

    private function persist(array $node, Kontrak $parent): Kontrak
    {
        $model = ! empty($node['id']) ? Kontrak::whereKey($node['id'])->where('id_parent', $parent->id)->where('jenis', $parent->jenis)->where('id_tahun', $parent->id_tahun)->lockForUpdate()->first() : new Kontrak;
        if (! $model) {
            $this->fail('root', 'Ada baris di luar induk ini atau struktur telah berubah. Muat ulang halaman.');
        }
        if ($model->exists) {
            $existing = Kontrak::where('id_parent', $model->id)->lockForUpdate()->pluck('id')->map(fn ($id) => (string) $id)->all();
            $submitted = array_map('strval', array_filter(array_column($node['children'], 'id')));
            if (array_diff($existing, $submitted)) {
                $this->fail('root', 'Rincian tersimpan tidak boleh dihapus lewat form ini. Muat ulang bila struktur berubah.');
            }
        }
        $model->fill([
            'id_parent' => $parent->id, 'jenis' => $parent->jenis, 'id_tahun' => $parent->id_tahun,
            'kode' => $node['kode'], 'nama' => $node['nama'], 'target' => $node['target'],
            'jenis_target' => $node['jenis_target'] ?? null,
            'calculation_type' => $node['calculation_type'],
        ])->save();
        $this->keys[$node['key']] = (string) $model->id;
        $this->saved[] = [$model, $node];
        foreach ($node['children'] as $child) {
            $this->persist($child, $model);
        }

        return $model;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
