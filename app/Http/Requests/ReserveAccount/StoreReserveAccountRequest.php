<?php

namespace App\Http\Requests\ReserveAccount;

use Illuminate\Foundation\Http\FormRequest;

class StoreReserveAccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
