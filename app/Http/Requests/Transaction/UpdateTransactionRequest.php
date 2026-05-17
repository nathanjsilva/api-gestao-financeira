<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
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
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'type' => ['sometimes', 'required', Rule::in(['income', 'expense'])],
            'status' => ['sometimes', 'required', Rule::in(['paid', 'pending'])],
            'competency' => ['sometimes', 'required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'is_recurring' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
