<?php

namespace App\Http\Requests\Transaction;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('description')) {
            $this->merge([
                'description' => trim((string) $this->input('description')),
            ]);
        }
    }

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
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'type' => ['sometimes', 'required', Rule::in(['income', 'expense'])],
            'status' => ['sometimes', 'required', Rule::in(['paid', 'pending'])],
            'competency' => ['sometimes', 'required', new CompetencyRule()],
            'is_recurring' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
