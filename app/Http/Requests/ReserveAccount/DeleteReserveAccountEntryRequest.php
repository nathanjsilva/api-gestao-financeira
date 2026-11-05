<?php

namespace App\Http\Requests\ReserveAccount;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteReserveAccountEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['competency' => $this->route('competencia')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'competency' => ['required', new CompetencyRule],
        ];
    }
}
