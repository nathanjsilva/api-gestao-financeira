<?php

namespace App\Http\Requests\Transaction;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => trim((string) $this->input('description')),
        ]);
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
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'status' => ['required', Rule::in(['paid', 'pending'])],
            'competency' => ['required', new CompetencyRule()],
            'is_recurring' => ['required', 'boolean'],
        ];
    }
}
