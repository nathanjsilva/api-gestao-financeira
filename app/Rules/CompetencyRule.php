<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CompetencyRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            $fail('O campo :attribute deve estar no formato YYYY-MM.');

            return;
        }

        [$ano, $mes] = explode('-', $value);

        if (! checkdate((int) $mes, 1, (int) $ano)) {
            $fail('O campo :attribute deve conter uma competencia mensal valida.');
        }
    }
}
