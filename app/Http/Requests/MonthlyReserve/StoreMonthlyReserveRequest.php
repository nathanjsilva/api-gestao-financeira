<?php

namespace App\Http\Requests\MonthlyReserve;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonthlyReserveRequest extends FormRequest
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
            'competency' => [
                'required',
                new CompetencyRule(),
                Rule::unique('monthly_reserves', 'competency')->where(
                    fn ($query) => $query->where('user_id', $this->user()?->id)
                ),
            ],
            'reserva_anterior' => ['required', 'numeric', 'min:0'],
            'investimento' => ['required', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
