<?php

namespace Tests\Feature;

use App\Models\BlockedName;
use App\Models\ContactSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContactAntiSpamTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ahmet Yılmaz',
            'email' => 'valid@example.com',
            'message' => 'Merhaba. Bu bir test mesajıdır. Üç cümle içerir.',
            'phone' => '+905551112233',
            'website' => '',
        ], $overrides);
    }

    public function test_rejects_short_message(): void
    {
        $res = $this->postJson('/contact', $this->validPayload(['message' => '20']));
        $res->assertStatus(422);
    }

    public function test_rejects_less_than_three_sentences(): void
    {
        $res = $this->postJson('/contact', $this->validPayload(['message' => 'Only one sentence.']));
        $res->assertStatus(422);
    }

    public function test_rejects_sqli_email(): void
    {
        $email = "(select(0)from(select(sleep(15)))v)/*";
        $res = $this->postJson('/contact', $this->validPayload(['email' => $email]));
        $res->assertStatus(422);
    }

    public function test_blocks_repeated_name_within_cooldown(): void
    {
        $payload = $this->validPayload();
        $this->postJson('/contact', $payload)->assertStatus(201);
        $this->postJson('/contact', $payload)->assertStatus(422);
    }

    public function test_allows_name_after_cooldown(): void
    {
        $payload = $this->validPayload();
        $this->postJson('/contact', $payload)->assertStatus(201);
        ContactSubmission::query()->update(['created_at' => Carbon::now()->subDays(40)]);
        $this->postJson('/contact', $payload)->assertStatus(201);
    }

    public function test_honeypot_rejects(): void
    {
        $res = $this->postJson('/contact', $this->validPayload(['website' => 'http://bot']));
        $res->assertStatus(400);
    }

    public function test_blocked_name(): void
    {
        BlockedName::create(['name' => 'JYupWMLW']);
        $res = $this->postJson('/contact', $this->validPayload(['name' => 'JYupWMLW']));
        $res->assertStatus(422);
    }
}

