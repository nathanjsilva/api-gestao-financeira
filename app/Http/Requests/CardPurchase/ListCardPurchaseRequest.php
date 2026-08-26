<?php

namespace App\Http\Requests\CardPurchase;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCardPurchaseRequest extends FormRequest
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
            'competency' => ['nullable', new CompetencyRule()],
            'card_id' => ['nullable', 'integer'],
            'card_category_id' => ['nullable', 'integer'],
            'payment_type' => ['nullable', Rule::in(['cash', 'installment'])],
        ];
    }
}
