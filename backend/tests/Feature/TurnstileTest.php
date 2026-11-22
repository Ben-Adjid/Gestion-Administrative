<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    public function test_disabled_without_secret_lets_requests_through(): void
    {
        config(['turnstile.enabled' => false]);

        $response = $this->postJson('/api/v1/contact', []);

        $this->assertNotSame('turnstile_failed', $response->json('code'));
    }

    public function test_enabled_refuses_missing_token(): void
    {
        config(['turnstile.enabled' => true, 'turnstile.secret' => 'secret-test']);
        Http::fake();

        $response = $this->postJson('/api/v1/contact', []);

        $response->assertStatus(422)->assertJsonPath('code', 'turnstile_failed');
        Http::assertNothingSent();
    }

    public function test_enabled_refuses_token_rejected_by_cloudflare(): void
    {
        config(['turnstile.enabled' => true, 'turnstile.secret' => 'secret-test']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false], 200)]);

        $response = $this->withHeader('X-Turnstile-Token', 'jeton-expire')->postJson('/api/v1/contact', []);

        $response->assertStatus(422)->assertJsonPath('code', 'turnstile_failed');
    }

    public function test_enabled_accepts_token_validated_by_cloudflare(): void
    {
        config(['turnstile.enabled' => true, 'turnstile.secret' => 'secret-test']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true], 200)]);

        $response = $this->withHeader('X-Turnstile-Token', 'jeton-valide')->postJson('/api/v1/contact', []);

        $this->assertNotSame('turnstile_failed', $response->json('code'));
        Http::assertSent(fn ($request) => $request['secret'] === 'secret-test' && $request['response'] === 'jeton-valide');
    }

    public function test_refuses_when_cloudflare_is_unreachable(): void
    {
        config(['turnstile.enabled' => true, 'turnstile.secret' => 'secret-test']);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

        $response = $this->withHeader('X-Turnstile-Token', 'jeton')->postJson('/api/v1/contact', []);

        $response->assertStatus(422)->assertJsonPath('code', 'turnstile_failed');
    }
}
