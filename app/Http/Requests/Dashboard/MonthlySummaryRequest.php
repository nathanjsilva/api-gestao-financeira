<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;

class MonthlySummaryRequest extends FormRequest
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
            'competency' => ['required', new CompetencyRule()],
        ];
    }
}
