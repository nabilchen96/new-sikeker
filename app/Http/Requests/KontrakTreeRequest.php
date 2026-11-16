<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KontrakTreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_parent' => ['required', 'integer', 'exists:kontraks,id'],
            'jenis' => ['required', Rule::in(['PK', 'KPI'])],
            'root' => ['required', 'array'],
        ];
    }
}
