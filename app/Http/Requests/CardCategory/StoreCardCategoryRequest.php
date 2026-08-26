<?php

namespace App\Http\Requests\CardCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCardCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('card_categories', 'name')->where(
                    fn ($query) => $query->where('user_id', $this->user()?->id)
                ),
            ],
            'active' => ['boolean'],
        ];
    }
}
