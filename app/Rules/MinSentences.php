<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinSentences implements ValidationRule
{
    public function __construct(private int $min = 3) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        $trimmed = trim($value);
        $parts = preg_split('/[.!?]+/u', $trimmed) ?: [];
        $count = 0;
        foreach ($parts as $p) {
            if (mb_strlen(trim($p)) >= 2) {
                $count++;
            }
        }
        if ($count < $this->min) {
            $fail('Please write at least ' . $this->min . ' sentences.');
        }
    }
}

