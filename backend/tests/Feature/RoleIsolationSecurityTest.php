<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleIsolationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role): Admin
    {
        return Admin::create([
            'name' => ucfirst($role),
            'email' => "{$role}@test.td",
            'password' => Hash::make('Secret123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function citizen(): User
    {
        return User::create([
            'name' => 'Citoyen',
            'email' => 'citoyen@test.td',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
            'is_banned' => false,
        ]);
    }

    public function test_api_sends_security_headers_and_rejects_unknown_origins(): void
    {
        $response = $this->getJson('/api/v1/categories', ['Origin' => 'https://site-inconnu.example'])
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
        // Avec une seule origine autorisée, la bibliothèque CORS renvoie toujours celle-ci :
        // le navigateur bloque donc le site inconnu. L'essentiel : son origine n'est jamais reprise.
        $this->assertNotSame('https://site-inconnu.example', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));

        $this->getJson('/api/v1/categories', ['Origin' => config('frontend.url')])
            ->assertHeader('Access-Control-Allow-Origin', config('frontend.url'));
    }

    public function test_citizen_can_delete_own_account_with_password_only(): void
    {
        $user = $this->citizen();
        $user->documents()->create(['type' => 'passeport', 'expiration_date' => now()->addYear()->toDateString()]);
        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->deleteJson('/api/v1/auth/account', ['password' => 'Mauvais123!'], $headers)->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->deleteJson('/api/v1/auth/account', ['password' => 'Secret123!'], $headers)->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('user_documents', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id, 'tokenable_type' => User::class]);
    }

    public function test_admin_token_cannot_delete_through_citizen_account_endpoint(): void
    {
        $this->actingAs($this->admin('super_admin'), 'sanctum');
        $this->deleteJson('/api/v1/auth/account', ['password' => 'Secret123!'])->assertForbidden();
    }

    public function test_admin_token_cannot_escalate_role_through_citizen_profile_endpoint(): void
    {
        $editor = $this->admin('editor');
        $this->actingAs($editor, 'sanctum');

        $this->putJson('/api/v1/auth/profile', ['name' => 'X', 'role' => 'super_admin']);

        $this->assertSame('editor', $editor->fresh()->role);
    }

    public function test_admin_token_is_rejected_on_citizen_endpoints(): void
    {
        $this->actingAs($this->admin('admin'), 'sanctum');

        $this->getJson('/api/v1/auth/me')->assertStatus(403);
        $this->putJson('/api/v1/auth/profile', ['name' => 'X'])->assertStatus(403);
    }

    public function test_citizen_token_is_rejected_on_every_admin_endpoint(): void
    {
        $this->actingAs($this->citizen(), 'sanctum');

        $this->getJson('/api/v1/admin/auth/me')->assertStatus(403);
        $this->getJson('/api/v1/admin/email/preferences')->assertStatus(403);
        $this->getJson('/api/v1/admin/demarches')->assertStatus(403);
    }

    public function test_citizen_cannot_mass_assign_protected_profile_fields(): void
    {
        $user = $this->citizen();
        $this->actingAs($user, 'sanctum');

        $this->putJson('/api/v1/auth/profile', [
            'name' => 'Nouveau nom',
            'is_banned' => true,
            'is_active' => false,
            'email' => 'autre@test.td',
            'password_reset_token' => 'forged',
        ])->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('Nouveau nom', $fresh->name);
        $this->assertFalse($fresh->is_banned);
        $this->assertTrue($fresh->is_active);
        $this->assertSame('citoyen@test.td', $fresh->email);
        $this->assertNull($fresh->password_reset_token);
    }

    public function test_banned_citizen_token_is_rejected(): void
    {
        $user = $this->citizen();
        $user->forceFill(['is_banned' => true])->save();
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/v1/auth/me')->assertStatus(403);
        $this->getJson('/api/v1/favorites')->assertStatus(403);
    }

    public function test_internal_analytics_are_not_public(): void
    {
        $this->getJson('/api/v1/analytics/dashboard')->assertNotFound();
        $this->getJson('/api/v1/admin/analytics')->assertStatus(401);
    }

    public function test_admin_login_does_not_reveal_whether_an_account_exists(): void
    {
        $this->admin('admin');

        $unknown = $this->postJson('/api/v1/admin/auth/login', ['email' => 'inconnu@test.td', 'password' => 'Faux123!'])->assertStatus(422);
        $wrong = $this->postJson('/api/v1/admin/auth/login', ['email' => 'admin@test.td', 'password' => 'Faux123!'])->assertStatus(422);

        $this->assertSame($unknown->json('errors'), $wrong->json('errors'));
    }

    public function test_citizen_credentials_never_open_the_admin_back_office(): void
    {
        $this->citizen();

        $this->postJson('/api/v1/admin/auth/login', ['email' => 'citoyen@test.td', 'password' => 'Secret123!'])
            ->assertStatus(422)
            ->assertJsonMissingPath('token');
    }

    public function test_public_registration_cannot_create_a_team_account(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Pirate',
            'email' => 'pirate@test.td',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'accept_terms' => true,
            'role' => 'super_admin',
        ])->assertCreated();

        $this->assertDatabaseMissing('admins', ['email' => 'pirate@test.td']);
        $this->postJson('/api/v1/admin/auth/login', ['email' => 'pirate@test.td', 'password' => 'Secret123!'])->assertStatus(422);
    }

    public function test_api_errors_are_json_even_without_accept_header(): void
    {
        $this->post('/api/v1/admin/auth/login', ['email' => 'x@y.td', 'password' => 'faux'])
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/json');
    }

    public function test_deactivated_admin_token_is_rejected(): void
    {
        $admin = $this->admin('admin');
        $admin->forceFill(['is_active' => false])->save();
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/v1/admin/stats')->assertStatus(403);
    }

    public function test_server_errors_do_not_leak_exception_messages(): void
    {
        $this->actingAs($this->citizen(), 'sanctum');

        $response = $this->getJson('/api/v1/favorites?sort=nonexistent_column');

        $this->assertNotSame(500, $response->status());
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
    }
}
