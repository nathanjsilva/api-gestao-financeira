<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
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
        $categoriaId = (int) $this->route('category');
        $categoria = Category::query()->find($categoriaId);
        $tipo = $this->input('type', $categoria?->type);

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($categoriaId)
                    ->where(function ($query) use ($tipo) {
                        return $query
                            ->where('user_id', $this->user()?->id)
                            ->where('type', $tipo);
                    }),
            ],
            'type' => ['sometimes', 'required', Rule::in(['income', 'expense'])],
        ];
    }
}
