<?php

namespace App\Http\Requests\ReserveAccount;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReserveAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'active' => ['sometimes', 'required', 'boolean'],
            'competency' => ['required_with:active', new CompetencyRule],
            'type' => ['prohibited'],
        ];
    }
}
