<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;

class CategoryComparisonRequest extends FormRequest
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
            'current_competency' => ['required', new CompetencyRule()],
            'previous_competency' => ['required', new CompetencyRule()],
        ];
    }
}
