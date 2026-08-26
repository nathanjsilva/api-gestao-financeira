<?php

namespace App\Http\Requests\CardCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCardCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
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
        $cardCategoryId = (int) $this->route('card_category');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('card_categories', 'name')
                    ->ignore($cardCategoryId)
                    ->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
