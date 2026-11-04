<?php

namespace App\Http\Requests\ReserveAccount;

use App\Rules\CompetencyRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertReserveAccountEntriesRequest extends FormRequest
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
        $usuarioId = $this->user()?->id;

        return [
            'competency' => ['required', new CompetencyRule()],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.account_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('reserve_accounts', 'id')->where(
                    fn ($query) => $query->where('user_id', $usuarioId)
                ),
            ],
            'entries.*.balance' => ['required', 'numeric', 'min:0'],
            'entries.*.note' => ['nullable', 'string'],
        ];
    }
}
