<?php

namespace App\Http\Requests\ReserveAccount;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;

class ListReserveAccountRequest extends FormRequest
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
            'competency' => ['sometimes', 'nullable', new CompetencyRule()],
        ];
    }
}
