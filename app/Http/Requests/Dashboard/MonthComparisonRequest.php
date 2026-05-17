<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;

class MonthComparisonRequest extends FormRequest
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
            'first_competency' => ['required', new CompetencyRule()],
            'second_competency' => ['required', new CompetencyRule()],
        ];
    }
}
