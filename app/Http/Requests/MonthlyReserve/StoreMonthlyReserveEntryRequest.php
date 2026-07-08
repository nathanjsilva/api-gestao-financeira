<?php

namespace App\Http\Requests\MonthlyReserve;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonthlyReserveEntryRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
