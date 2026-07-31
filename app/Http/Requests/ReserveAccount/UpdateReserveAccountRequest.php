<?php

namespace App\Http\Requests\ReserveAccount;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReserveAccountRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
