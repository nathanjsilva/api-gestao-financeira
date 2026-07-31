<?php

namespace App\Http\Requests\ReserveAccount;

use Illuminate\Foundation\Http\FormRequest;

class UpsertReserveAccountEntryRequest extends FormRequest
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
            'balance' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}
