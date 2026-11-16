<?php

namespace App\Http\Requests;

use App\Models\Kontrak;
use App\Services\KontrakCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KontrakBagianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // The routes already require an authenticated session.
    }

    protected function prepareForValidation(): void
    {
        $values = ['target' => $this->input('target')];
        $config = $this->input('calculation_config', []);
        foreach (['factor', 'baseline'] as $field) {
            if (isset($config[$field]) && is_string($config[$field])) {
                $config[$field] = str_replace(',', '.', $config[$field]);
            }
        }
        if (isset($config['weights']) && is_array($config['weights'])) {
            $config['weights'] = array_map(fn ($value) => is_string($value) ? str_replace(',', '.', $value) : $value, $config['weights']);
        }
        if (is_string($values['target'])) {
            $values['target'] = str_replace(',', '.', $values['target']);
        }
        $values['calculation_config'] = $config;
        $this->merge($values);
    }

    public function rules(): array
    {
        $childIds = $this->filled('id')
            ? Kontrak::where('id_parent', $this->input('id'))->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];

        return [
            'id' => $this->is('update-bagian') ? ['required', 'integer', 'exists:kontraks,id'] : ['prohibited'],
            'id_parent' => ['required', 'integer', Rule::exists('kontraks', 'id')->where('jenis', $this->input('jenis'))],
            'jenis' => ['required', Rule::in(['PK', 'KPI'])],
            'kode' => ['required', 'string', 'max:255'],
            'sub_indikator' => ['required', 'string'],
            'target' => ['nullable', 'required_if:calculation_type,manual', 'numeric', 'decimal:0,6', 'min:0', 'max:9999999999999999'],
            'jenis_target' => ['nullable', 'string', 'max:255'],
            'calculation_type' => ['nullable', Rule::in(KontrakCalculator::TYPES)],
            'calculation_config' => ['array'],
            'calculation_config.numerator_id' => ['nullable', 'required_if:calculation_type,ratio,offset_ratio', Rule::in($childIds)],
            'calculation_config.denominator_id' => ['nullable', 'required_if:calculation_type,ratio,offset_ratio', 'different:calculation_config.numerator_id', Rule::in($childIds)],
            'calculation_config.source_id' => ['nullable', 'required_if:calculation_type,scale', Rule::in($childIds)],
            'calculation_config.factor' => ['nullable', 'required_if:calculation_type,ratio,scale,offset_ratio', 'numeric', 'min:0', 'max:1000000'],
            'calculation_config.baseline' => ['nullable', 'required_if:calculation_type,offset_ratio', 'numeric', 'min:0', 'max:1000000'],
            'calculation_config.weights' => ['nullable', 'array'],
            'calculation_config.weights.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->input('calculation_type') === 'weighted_sum') {
                $ids = Kontrak::where('id_parent', $this->input('id'))->pluck('id')->map(fn ($id) => (string) $id)->all();
                $weights = $this->input('calculation_config.weights', []) ?? [];
                if (array_diff(array_map('strval', array_keys($weights)), $ids)) {
                    $validator->errors()->add('calculation_config.weights', 'Bobot harus berasal dari anak langsung indikator ini.');
                }
            }
            if ($this->input('calculation_type') === 'formula' &&
                (! $this->filled('id') || Kontrak::whereKey($this->input('id'))->value('calculation_type') !== 'formula')) {
                $validator->errors()->add('calculation_type', 'Tulis rumus melalui Edit seperti Excel.');
            }
            // A parent cannot be moved below itself or one of its descendants.
            $parentId = $this->input('id_parent');
            $visited = [];
            while ($parentId !== null) {
                if (isset($visited[$parentId]) || ($this->filled('id') && (string) $parentId === (string) $this->input('id'))) {
                    $validator->errors()->add('id_parent', 'Induk tidak boleh membentuk siklus.');
                    break;
                }
                $visited[$parentId] = true;
                $parentId = Kontrak::whereKey($parentId)->value('id_parent');
            }
        });
    }

    public function calculationSettings(): array
    {
        $type = $this->input('calculation_type') ?: null;
        $config = $this->input('calculation_config', []);
        if ($type === 'formula') {
            return ['calculation_type' => 'formula', 'calculation_config' => Kontrak::findOrFail($this->input('id'))->calculation_config];
        }
        $keys = match ($type) {
            'ratio' => ['numerator_id', 'denominator_id', 'factor'],
            'offset_ratio' => ['numerator_id', 'denominator_id', 'factor', 'baseline'],
            'scale' => ['source_id', 'factor'],
            'weighted_sum' => ['weights'],
            default => [],
        };

        return [
            'calculation_type' => $type,
            'calculation_config' => $type ? array_intersect_key($config, array_flip($keys)) : null,
        ];
    }
}
