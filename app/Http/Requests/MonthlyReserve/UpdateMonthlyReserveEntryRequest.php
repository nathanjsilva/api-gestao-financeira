<?php

namespace App\Http\Requests\MonthlyReserve;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMonthlyReserveEntryRequest extends FormRequest
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
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
        ];
    }
}
