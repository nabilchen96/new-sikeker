<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KontrakSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_parent' => [$this->is('save-kontrak-sheet') ? 'required' : 'nullable', 'integer', 'exists:kontraks,id'],
            'jenis' => ['required', Rule::in(['PK', 'KPI'])],
            'rows' => ['required', 'array', 'min:1', 'max:200'],
            'rows.*' => ['array'],
            'rows.*.key' => ['required', 'string', 'max:80'],
            'rows.*.id' => ['nullable', 'integer', 'min:1'],
            'rows.*.parent_key' => ['nullable', 'string', 'max:80'],
            'rows.*.kode' => ['nullable', 'string', 'max:255'],
            'rows.*.nama' => ['nullable', 'string', 'max:5000'],
            'rows.*.jenis_target' => ['nullable', 'string', 'max:255'],
            'rows.*.input' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
