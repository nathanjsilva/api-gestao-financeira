<?php

namespace App\Http\Requests\MonthlyReserve;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMonthlyReserveRequest extends FormRequest
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
        $reservaMensalId = (int) $this->route('monthly_reserve');

        return [
            'competency' => [
                'sometimes',
                'required',
                new CompetencyRule(),
                Rule::unique('monthly_reserves', 'competency')
                    ->ignore($reservaMensalId)
                    ->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'reserva_anterior' => ['sometimes', 'required', 'numeric', 'min:0'],
            'investimento' => ['sometimes', 'required', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
