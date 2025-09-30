<?php

namespace App\Rules;

use App\Models\ContactSubmission;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class UniqueNameCooldown implements ValidationRule
{
    public function __construct(private ?int $days = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }
        $days = $this->days ?? (int) config('antispam.name_cooldown_days', 30);
        $since = Carbon::now()->subDays($days);
        $exists = ContactSubmission::where('name', $value)
            ->where('created_at', '>=', $since)
            ->exists();
        if ($exists) {
            $fail('This name has recently submitted a message; please use your real full name.');
        }
    }
}

