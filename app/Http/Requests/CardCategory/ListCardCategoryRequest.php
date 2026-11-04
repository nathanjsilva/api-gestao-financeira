<?php

namespace App\Http\Requests\CardCategory;

use Illuminate\Foundation\Http\FormRequest;

class ListCardCategoryRequest extends FormRequest
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
            'include_inactive' => ['sometimes', 'boolean'],
        ];
    }
}
