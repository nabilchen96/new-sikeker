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

    private bool $allowReparent = false;

    private array $movableModels = [];

    public function save(int $parentId, string $jenis, array $tree, bool $allowReparent = false): Kontrak
    {
        $this->keys = $this->ids = $this->saved = [];
        $this->count = 0;
        $this->allowReparent = $allowReparent;
        $this->movableModels = [];
        $tree = $this->validateNode($tree, 'root', 0);
        $this->validateDependencies($tree);

        return DB::transaction(function () use ($parentId, $jenis, $tree) {
            $parent = Kontrak::whereKey($parentId)->where('jenis', $jenis)->lockForUpdate()->first();
            if (! $parent || ! DB::table('tahuns')->where('id', $parent->id_tahun)->where('status', 'Aktif')->exists()) {
                $this->fail('id_parent', 'Induk harus berasal dari jenis dan tahun aktif yang sama.');
            }
            if ($this->allowReparent) {
                $this->prepareReparenting($tree, $parent);
            }
            $root = $this->persist($tree, $parent);
            foreach ($this->saved as [$model, $data]) {
                $config = $data['calculation_config'];
                foreach (['source_id', 'numerator_id', 'denominator_id'] as $field) {
                    if (array_key_exists($field, $config)) {
                        $config[$field] = isset($this->keys[$config[$field] ?? '']) ? $this->keys[$config[$field]] : null;
                    }
                }
                if (isset($config['ast'])) {
                    $config['ast'] = (new KontrakFormula)->mapReferences($config['ast'], fn ($key) => $this->keys[$key]);
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
        if ($type === 'formula') {
            if (! is_array($config['ast'] ?? null)) {
                $this->fail($path, 'Rumus tabel tidak valid.');
            }
            $clean['ast'] = $config['ast'];
        }
        $node['calculation_config'] = $clean;

        return $node;
    }

    private function validateDependencies(array $tree): void
    {
        $nodes = [];
        $collect = function ($node) use (&$collect, &$nodes) {
            $nodes[$node['key']] = $node;
            foreach ($node['children'] as $child) {
                $collect($child);
            }
        };
        $collect($tree);
        $dependencies = [];
        foreach ($nodes as $key => $node) {
            $config = $node['calculation_config'];
            if ($node['calculation_type'] === 'formula') {
                try {
                    (new KontrakFormula)->validate($config['ast'], array_map('strval', array_keys($nodes)));
                } catch (\DomainException $error) {
                    $this->fail('root', $error->getMessage());
                }
                $dependencies[$key] = (new KontrakFormula)->references($config['ast']);
            } else {
                $dependencies[$key] = match ($node['calculation_type']) {
                    'manual' => [],
                    'sum' => $config['child_ids'] ?? array_column($node['children'], 'key'),
                    'weighted_sum' => array_keys(array_filter($config['weights'] ?? [], fn ($weight) => (float) $weight !== 0.0)),
                    'scale' => array_filter([$config['source_id'] ?? null]),
                    default => array_filter([$config['numerator_id'] ?? null, $config['denominator_id'] ?? null]),
                };
            }
        }
        $visiting = [];
        $done = [];
        $walk = function ($key) use (&$walk, &$visiting, &$done, $dependencies) {
            if (isset($done[$key])) {
                return;
            }
            if (isset($visiting[$key])) {
                $this->fail('root', 'Rumus membentuk siklus. Periksa referensi sel.');
            }
            $visiting[$key] = true;
            foreach ($dependencies[$key] ?? [] as $source) {
                $walk($source);
            }
            unset($visiting[$key]);
            $done[$key] = true;
        };
        foreach (array_keys($nodes) as $key) {
            $walk($key);
        }
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

    private function prepareReparenting(array $tree, Kontrak $parent): void
    {
        if ($parent->id_parent !== null) {
            $this->fail('id_parent', 'Pilih sasaran kinerja pada tingkat teratas.');
        }
        if (! empty($tree['id'])) {
            $root = Kontrak::whereKey($tree['id'])->where('jenis', $parent->jenis)->where('id_tahun', $parent->id_tahun)->lockForUpdate()->first();
            if (! $root || ! Kontrak::whereKey($root->id_parent)->whereNull('id_parent')->where('jenis', $parent->jenis)->where('id_tahun', $parent->id_tahun)->lockForUpdate()->exists()) {
                $this->fail('root', 'Indikator harus berasal dari jenis dan tahun yang sama.');
            }
            $pending = [$root];
            while ($pending) {
                $model = array_pop($pending);
                if (isset($this->movableModels[(string) $model->id])) {
                    $this->fail('root', 'Susunan lama mengandung siklus.');
                }
                if ($model->jenis !== $parent->jenis || (string) $model->id_tahun !== (string) $parent->id_tahun) {
                    $this->fail('root', 'Rincian harus berasal dari jenis dan tahun yang sama.');
                }
                $this->movableModels[(string) $model->id] = $model;
                foreach (Kontrak::where('id_parent', $model->id)->lockForUpdate()->get() as $child) {
                    $pending[] = $child;
                }
            }
        }
        $existing = array_map('strval', array_keys($this->movableModels));
        $submitted = array_map('strval', array_keys($this->ids));
        if (array_diff($existing, $submitted)) {
            $this->fail('root', 'Rincian tersimpan harus tetap disertakan. Muat ulang bila struktur berubah.');
        }
        if (array_diff($submitted, $existing)) {
            $this->fail('root', 'Baris dari indikator lain tidak boleh dipindahkan ke tabel ini.');
        }
    }

    private function persist(array $node, Kontrak $parent): Kontrak
    {
        $model = empty($node['id']) ? new Kontrak : ($this->allowReparent
            ? ($this->movableModels[(string) $node['id']] ?? null)
            : Kontrak::whereKey($node['id'])->where('id_parent', $parent->id)->where('jenis', $parent->jenis)->where('id_tahun', $parent->id_tahun)->lockForUpdate()->first());
        if (! $model) {
            $this->fail('root', 'Ada baris di luar induk ini atau struktur telah berubah. Muat ulang halaman.');
        }
        if ($model->exists && ! $this->allowReparent) {
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
