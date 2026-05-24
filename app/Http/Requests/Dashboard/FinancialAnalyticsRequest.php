<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;

class FinancialAnalyticsRequest extends FormRequest
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
            'months' => ['nullable', 'integer', 'min:2', 'max:12'],
        ];
    }
}

