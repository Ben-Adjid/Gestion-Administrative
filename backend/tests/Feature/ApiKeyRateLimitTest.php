<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Cahier des charges, module 9 : 100 requêtes/minute par IP, clés partenaires illimitées et comptées. */
class ApiKeyRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role): Admin
    {
        return Admin::create([
            'name' => ucfirst($role), 'email' => "{$role}@apikey.test",
            'password' => Hash::make('Secret123!'), 'role' => $role, 'is_active' => true,
        ]);
    }

    private function hit(int $times, array $headers = [], string $ip = '10.0.0.1'): int
    {
        $status = 0;
        for ($i = 0; $i < $times; $i++) {
            $status = $this->withServerVariables(['REMOTE_ADDR' => $ip])->getJson('/api/v1/categories', $headers)->status();
        }

        return $status;
    }

    public function test_public_read_is_limited_to_100_requests_per_minute_per_ip(): void
    {
        $this->assertSame(200, $this->hit(100));
        $this->assertSame(429, $this->hit(1));
        // Une autre adresse IP garde son propre quota.
        $this->assertSame(200, $this->hit(1, [], '10.0.0.2'));
    }

    public function test_internal_key_of_the_site_server_is_not_limited(): void
    {
        config(['api.internal_key' => 'secret-interne-de-test']);

        $this->assertSame(200, $this->hit(110, ['X-Internal-Key' => 'secret-interne-de-test']));
        // Une mauvaise valeur ne donne aucun passe-droit.
        $this->assertSame(200, $this->hit(100, ['X-Internal-Key' => 'mauvaise'], '10.0.0.3'));
        $this->assertSame(429, $this->hit(1, ['X-Internal-Key' => 'mauvaise'], '10.0.0.3'));
    }

    public function test_empty_internal_key_configuration_grants_nothing(): void
    {
        config(['api.internal_key' => '']);

        $this->assertSame(200, $this->hit(100, ['X-Internal-Key' => '']));
        $this->assertSame(429, $this->hit(1, ['X-Internal-Key' => '']));
    }

    public function test_partner_key_is_unlimited_counted_and_revocable(): void
    {
        [$key, $plain] = ApiKey::generate('Partenaire test', null);

        $this->assertSame(200, $this->hit(110, ['X-Api-Key' => $plain]));
        $key->refresh();
        $this->assertSame(110, $key->requests_count);
        $this->assertNotNull($key->last_used_at);

        $key->update(['is_active' => false]);
        // Clé révoquée : retour au quota public (les 110 requêtes avec clé ne l'avaient pas entamé).
        $this->assertSame(200, $this->hit(100, ['X-Api-Key' => $plain]));
        $this->assertSame(429, $this->hit(1, ['X-Api-Key' => $plain]));
        $this->assertSame(110, $key->fresh()->requests_count);
    }

    public function test_plain_key_is_never_stored(): void
    {
        [$key, $plain] = ApiKey::generate('Partenaire', null);

        $this->assertStringStartsWith('dtk_', $plain);
        $this->assertNotSame($plain, $key->getRawOriginal('key_hash'));
        $this->assertSame(hash('sha256', $plain), $key->getRawOriginal('key_hash'));
        $this->assertArrayNotHasKey('key_hash', $key->toArray());
    }

    public function test_only_super_admin_manages_partner_keys(): void
    {
        foreach (['admin', 'editor'] as $role) {
            Sanctum::actingAs($this->admin($role));
            $this->getJson('/api/v1/admin/api-keys')->assertForbidden();
            $this->postJson('/api/v1/admin/api-keys', ['name' => 'X'])->assertForbidden();
        }
        $this->assertSame(0, ApiKey::count());

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/admin/api-keys')->assertUnauthorized();
    }

    public function test_super_admin_creates_lists_revokes_and_deletes_a_key(): void
    {
        $super = $this->admin('super_admin');
        Sanctum::actingAs($super);

        $created = $this->postJson('/api/v1/admin/api-keys', ['name' => 'Ministère partenaire'])->assertCreated()->json('data');
        $this->assertStringStartsWith('dtk_', $created['key']);
        $this->assertSame(substr($created['key'], 0, 12), $created['key_prefix']);

        $list = $this->getJson('/api/v1/admin/api-keys')->assertOk()->json('data');
        $this->assertCount(1, $list);
        $this->assertArrayNotHasKey('key', $list[0], 'La clé en clair ne doit plus jamais être renvoyée.');
        $this->assertArrayNotHasKey('key_hash', $list[0]);

        $this->patchJson("/api/v1/admin/api-keys/{$created['id']}", ['is_active' => false])->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertNull(ApiKey::findActive($created['key']));

        $this->postJson('/api/v1/admin/api-keys', [])->assertStatus(422)->assertJsonValidationErrors('name');

        $this->deleteJson("/api/v1/admin/api-keys/{$created['id']}")->assertNoContent();
        $this->assertSame(0, ApiKey::count());

        $actions = ActivityLog::where('admin_id', $super->id)->pluck('action')->all();
        $this->assertSame(['api_key_created', 'api_key_updated', 'api_key_deleted'], $actions);
        $this->assertFalse(ActivityLog::where('description', 'like', '%' . $created['key'] . '%')->exists(), 'La clé en clair ne doit pas figurer dans le journal.');
    }

    public function test_cors_preflight_accepts_the_partner_key_header(): void
    {
        $allowed = $this->call('OPTIONS', '/api/v1/categories', [], [], [], [
            'HTTP_ORIGIN' => config('cors.allowed_origins')[0] ?? 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'x-api-key',
        ])->headers->get('Access-Control-Allow-Headers');

        $this->assertContains('x-api-key', array_map('trim', explode(',', strtolower((string) $allowed))));
    }
}
