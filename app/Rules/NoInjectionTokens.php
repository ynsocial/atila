<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoInjectionTokens implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }
        $patterns = config('antispam.sqli_patterns', []);
        $hay = strtolower($value);
        foreach ($patterns as $re) {
            if (@preg_match('/' . $re . '/i', $hay)) {
                if (preg_match('/' . $re . '/i', $hay)) {
                    $fail('Invalid ' . $attribute . '.');
                    return;
                }
            }
        }

        if (str_contains($hay, "'\"+") || str_contains($hay, "'||")) {
            $fail('Invalid ' . $attribute . '.');
        }
    }
}

