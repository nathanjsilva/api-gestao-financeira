<?php

namespace App\Http\Requests\CardPurchase;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCardPurchaseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('description')) {
            $this->merge(['description' => trim((string) $this->input('description'))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Uma compra parcelada e regerada por completo a cada edicao (as parcelas
     * sao dado derivado, nao ha diff incremental) — por isso a atualizacao
     * exige o mesmo conjunto completo de campos do cadastro.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'card_id' => [
                'required',
                'integer',
                Rule::exists('cards', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'card_category_id' => [
                'required',
                'integer',
                Rule::exists('card_categories', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()?->id)
                ),
            ],
            'description' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'purchase_date' => ['required', 'date'],
            'reference_competency' => ['required', new CompetencyRule()],
            'payment_type' => ['required', Rule::in(['cash', 'installment'])],
            'installments_total' => ['required_if:payment_type,installment', 'nullable', 'integer', 'min:1', 'max:60'],
            'starting_installment_number' => [
                'required_if:payment_type,installment',
                'nullable',
                'integer',
                'min:1',
                'lte:installments_total',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'installments_total.required_if' => 'Informe a quantidade total de parcelas.',
            'starting_installment_number.required_if' => 'Informe em qual parcela a compra se encontra atualmente.',
            'starting_installment_number.lte' => 'A parcela atual nao pode ser maior que o total de parcelas.',
        ];
    }
}
