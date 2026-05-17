<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;

class MonthlyEvolutionRequest extends FormRequest
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
            'start_competency' => ['required', new CompetencyRule()],
            'end_competency' => ['required', new CompetencyRule()],
        ];
    }
}
