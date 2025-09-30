<?php

namespace App\Rules;

use App\Models\BlockedName;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidHumanName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('Please provide your real full name.');
            return;
        }

        $name = trim($value);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            $fail('Please provide your real full name.');
            return;
        }

        if (BlockedName::where('name', $name)->exists()) {
            $fail('This name is blocked.');
            return;
        }

        if (!preg_match('/^[\p{L}\s\'\.-]+$/u', $name)) {
            $fail('Please provide your real full name.');
            return;
        }

        // Heuristic: 4+ consecutive Latin consonants or 7-12 all-caps without vowels
        if (preg_match('/(?i)[bcdfghjklmnpqrstvwxyz]{4,}/u', $name)) {
            $fail('Please provide your real full name.');
            return;
        }
        if (preg_match('/^[A-Z]{7,12}$/', $name) && !preg_match('/[AEIİOÖUÜAEOUI]/u', $name)) {
            $fail('Please provide your real full name.');
        }
    }
}

