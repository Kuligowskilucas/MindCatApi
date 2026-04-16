<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('A senha deve ser um texto.');
            return;
        }

        if (strlen($value) < 8) {
            $fail('A senha deve ter pelo menos 8 caracteres.');
            return;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $fail('A senha deve ter pelo menos uma letra maiúscula.');
            return;
        }

        if (!preg_match('/[a-z]/', $value)) {
            $fail('A senha deve ter pelo menos uma letra minúscula.');
            return;
        }

        if (!preg_match('/[0-9]/', $value)) {
            $fail('A senha deve ter pelo menos um número.');
            return;
        }
    }
}