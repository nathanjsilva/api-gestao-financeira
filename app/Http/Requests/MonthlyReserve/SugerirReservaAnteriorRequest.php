<?php

namespace App\Http\Requests\MonthlyReserve;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;

class SugerirReservaAnteriorRequest extends FormRequest
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
            'competency' => ['required', new CompetencyRule()],
        ];
    }
}
