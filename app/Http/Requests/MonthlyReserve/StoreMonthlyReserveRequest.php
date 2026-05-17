<?php

namespace App\Http\Requests\MonthlyReserve;

use Illuminate\Foundation\Http\FormRequest;

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
            'competency' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'reserva_anterior' => ['required', 'numeric', 'min:0'],
            'investimento' => ['required', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
