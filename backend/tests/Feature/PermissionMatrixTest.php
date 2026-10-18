<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Models\Admin;
use App\Models\Categorie;
use App\Models\Demarche;
use App\Models\Signalement;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categorie = Categorie::create(['name' => 'Identité', 'slug' => 'identite', 'is_active' => true]);
    }

    private function admin(string $role): Admin
    {
        return Admin::create([
            'name' => ucfirst($role),
            'email' => "{$role}@matrix.test",
            'password' => Hash::make('Secret123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function citizen(string $email = 'a@matrix.test'): User
    {
        return User::create(['name' => 'Citoyen', 'email' => $email, 'password' => Hash::make('Secret123!')]);
    }

    private function demarche(string $status = 'published', string $title = 'Carte nationale'): Demarche
    {
        return Demarche::create([
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'description' => 'Description',
            'categorie_id' => $this->categorie->id,
            'cost' => '5 000 FCFA',
            'institution' => 'Ministère',
            'status' => $status,
        ]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Nouvelle démarche',
            'description' => 'Description complète',
            'categorie_id' => $this->categorie->id,
            'cost' => '1 000 FCFA',
            'institution' => 'Mairie',
        ], $extra);
    }

    // ---------------- Démarches ----------------

    public function test_editor_creation_is_forced_to_draft_even_if_published_is_requested(): void
    {
        $this->actingAs($this->admin('editor'), 'sanctum');

        $this->postJson('/api/v1/admin/demarches', $this->payload(['status' => 'published']))
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_admin_can_create_a_published_demarche(): void
    {
        $this->actingAs($this->admin('admin'), 'sanctum');

        $this->postJson('/api/v1/admin/demarches', $this->payload(['status' => 'published']))
            ->assertCreated()
            ->assertJsonPath('data.status', 'published');
    }

    public function test_editor_cannot_publish_archive_or_change_status(): void
    {
        $draft = $this->demarche('draft');
        $this->actingAs($this->admin('editor'), 'sanctum');

        $this->patchJson("/api/v1/admin/demarches/{$draft->id}/publish")->assertForbidden();
        $this->deleteJson("/api/v1/admin/demarches/{$draft->id}")->assertForbidden();
        $this->putJson("/api/v1/admin/demarches/{$draft->id}", ['status' => 'published'])->assertForbidden();

        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertNull($draft->fresh()->deleted_at);
    }

    public function test_editor_can_edit_content_and_the_change_is_audited(): void
    {
        $demarche = $this->demarche();
        $editor = $this->admin('editor');
        $this->actingAs($editor, 'sanctum');

        $this->putJson("/api/v1/admin/demarches/{$demarche->id}", ['cost' => '7 500 FCFA'])->assertOk();

        $this->assertSame('7 500 FCFA', $demarche->fresh()->cost);
        $this->assertDatabaseHas('activity_logs', ['admin_id' => $editor->id, 'action' => 'update_demarche', 'subject_id' => $demarche->id]);
    }

    public function test_admin_publishes_a_draft_and_it_becomes_public(): void
    {
        $draft = $this->demarche('draft');
        $this->getJson("/api/v1/demarches/{$draft->slug}")->assertNotFound();

        $this->actingAs($this->admin('admin'), 'sanctum');
        $this->patchJson("/api/v1/admin/demarches/{$draft->id}/publish")->assertOk();

        $this->assertSame('published', $draft->fresh()->status);
        $this->assertNotNull($draft->fresh()->last_verified_at);
    }

    // ---------------- Catégories, signalements, exports ----------------

    public function test_editor_reads_categories_but_cannot_write_them(): void
    {
        $this->actingAs($this->admin('editor'), 'sanctum');

        $this->getJson('/api/v1/admin/categories')->assertOk();
        $this->postJson('/api/v1/admin/categories', ['name' => 'Voyage'])->assertForbidden();
        $this->putJson("/api/v1/admin/categories/{$this->categorie->id}", ['name' => 'X'])->assertForbidden();
        $this->deleteJson("/api/v1/admin/categories/{$this->categorie->id}")->assertForbidden();
    }

    public function test_editor_reads_signalements_without_contact_data_and_cannot_moderate(): void
    {
        $signalement = Signalement::create([
            'demarche_id' => $this->demarche()->id,
            'type' => 'error',
            'message' => 'Le coût a changé',
            'author_email' => 'citoyen@exemple.td',
            'ip_address' => '127.0.0.1',
            'status' => 'pending',
        ]);
        $this->actingAs($this->admin('editor'), 'sanctum');

        $this->getJson("/api/v1/admin/signalements/{$signalement->id}")
            ->assertOk()
            ->assertJsonPath('data.author_email', null)
            ->assertJsonPath('data.has_contact', true);

        $this->patchJson("/api/v1/admin/signalements/{$signalement->id}/status", ['status' => 'resolved'])->assertForbidden();
        $this->deleteJson("/api/v1/admin/signalements/{$signalement->id}")->assertForbidden();
        $this->assertSame('pending', $signalement->fresh()->status);
    }

    public function test_csv_export_is_open_to_admin_but_not_to_editor(): void
    {
        $this->actingAs($this->admin('editor'), 'sanctum');
        $this->get('/api/v1/admin/export/demarches')->assertForbidden();

        $this->actingAs($this->admin('admin'), 'sanctum');
        $this->get('/api/v1/admin/export/demarches')->assertOk();
    }

    public function test_csv_export_neutralises_spreadsheet_formulas(): void
    {
        $this->demarche('published', '=HYPERLINK("http://evil")');
        $this->actingAs($this->admin('admin'), 'sanctum');

        $csv = $this->get('/api/v1/admin/export/demarches')->assertOk()->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(';=HYPERLINK', $csv);
    }

    public function test_only_super_admin_manages_team_and_reads_audit_log(): void
    {
        foreach (['editor', 'admin'] as $role) {
            $this->actingAs($this->admin($role), 'sanctum');
            $this->getJson('/api/v1/admin/users')->assertForbidden();
            $this->getJson('/api/v1/admin/activity-logs')->assertForbidden();
        }

        $this->actingAs($this->admin('super_admin'), 'sanctum');
        $this->getJson('/api/v1/admin/users')->assertOk();
        $this->getJson('/api/v1/admin/activity-logs')->assertOk();
    }

    public function test_last_active_super_admin_cannot_be_demoted_or_self_demote(): void
    {
        $super = $this->admin('super_admin');
        $this->actingAs($super, 'sanctum');

        $this->putJson("/api/v1/admin/users/{$super->id}", ['role' => 'editor'])->assertStatus(422);
        $this->assertSame('super_admin', $super->fresh()->role);
    }

    public function test_role_change_revokes_existing_tokens(): void
    {
        $super = $this->admin('super_admin');
        $editor = $this->admin('editor');
        $editor->createToken('old');

        $this->actingAs($super, 'sanctum');
        $this->putJson("/api/v1/admin/users/{$editor->id}", ['role' => 'admin'])->assertOk();

        $this->assertSame(0, $editor->tokens()->count());
    }

    // ---------------- Espace citoyen ----------------

    public function test_citizen_favorites_are_isolated_and_drafts_cannot_be_saved(): void
    {
        $published = $this->demarche();
        $draft = $this->demarche('draft', 'Brouillon interne');
        $alice = $this->citizen('alice@matrix.test');
        $bob = $this->citizen('bob@matrix.test');

        $this->actingAs($alice, 'sanctum');
        $this->postJson("/api/v1/favorites/{$published->id}")->assertCreated();
        $this->postJson("/api/v1/favorites/{$published->id}")->assertOk();
        $this->postJson("/api/v1/favorites/{$draft->id}")->assertNotFound();
        $this->getJson('/api/v1/favorites')->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($bob, 'sanctum');
        $this->getJson('/api/v1/favorites')->assertOk()->assertJsonCount(0, 'data');
        $this->deleteJson("/api/v1/favorites/{$published->id}")->assertNotFound();
        $this->assertSame(1, $alice->favorites()->count());
    }

    public function test_citizen_documents_are_isolated_from_other_citizens(): void
    {
        $alice = $this->citizen('alice@matrix.test');
        $bob = $this->citizen('bob@matrix.test');

        $this->actingAs($alice, 'sanctum');
        $id = $this->postJson('/api/v1/me/documents', ['type' => 'cni', 'expiration_date' => now()->addMonths(2)->toDateString()])
            ->assertCreated()
            ->assertJsonPath('data.state', 'soon')
            ->json('data.id');

        $this->actingAs($bob, 'sanctum');
        $this->putJson("/api/v1/me/documents/{$id}", ['type' => 'passeport', 'expiration_date' => '2030-01-01'])->assertNotFound();
        $this->deleteJson("/api/v1/me/documents/{$id}")->assertNotFound();
        $this->getJson('/api/v1/me/documents')->assertOk()->assertJsonCount(0, 'data');

        $this->assertSame('cni', UserDocument::find($id)->type);
    }

    public function test_citizen_dashboard_reports_only_own_alerts(): void
    {
        $alice = $this->citizen('alice@matrix.test');
        $alice->documents()->create(['type' => 'passeport', 'expiration_date' => now()->addDays(10)->toDateString()]);
        $this->citizen('bob@matrix.test')->documents()->create(['type' => 'cni', 'expiration_date' => now()->addDays(5)->toDateString()]);

        $this->actingAs($alice, 'sanctum');
        $this->getJson('/api/v1/me/dashboard')
            ->assertOk()
            ->assertJsonPath('data.counters.documents', 1)
            ->assertJsonPath('data.counters.alerts', 1)
            ->assertJsonCount(1, 'data.alerts');
    }

    public function test_reminder_command_sends_each_threshold_once(): void
    {
        Mail::fake();
        $user = $this->citizen();
        $doc = $user->documents()->create(['type' => 'cni', 'expiration_date' => now()->addDays(25)->toDateString()]);

        $this->artisan('documents:send-reminders')->assertSuccessful();
        $this->artisan('documents:send-reminders')->assertSuccessful();

        Mail::assertQueued(NotificationMail::class, 1);
        $this->assertSame(30, $doc->fresh()->last_reminder_days);
    }

    public function test_password_reset_token_is_stored_hashed_and_single_use(): void
    {
        Mail::fake();
        $user = $this->citizen();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

        $plain = null;
        Mail::assertQueued(\App\Mail\PasswordResetMail::class, function ($mail) use (&$plain) {
            $plain = $mail->resetToken ?? $mail->token ?? null;
            return true;
        });
        $this->assertNotNull($plain);
        $this->assertNotSame($plain, $user->fresh()->password_reset_token);

        $body = ['email' => $user->email, 'token' => $plain, 'password' => 'Nouveau123!', 'password_confirmation' => 'Nouveau123!'];
        $this->postJson('/api/v1/auth/reset-password', $body)->assertOk();
        $this->postJson('/api/v1/auth/reset-password', $body)->assertStatus(422);
    }

    public function test_a_citizen_can_never_read_or_change_another_citizens_documents_or_favorites(): void
    {
        $owner = $this->citizen('owner@matrix.test');
        $intruder = $this->citizen('intruder@matrix.test');
        $demarche = $this->demarche();
        $document = $owner->documents()->create(['type' => 'passport', 'label' => 'Passeport', 'expiration_date' => now()->addYear()->toDateString()]);
        $owner->favorites()->create(['demarche_id' => $demarche->id, 'note' => 'Note privée']);

        $token = $intruder->createToken('test')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/me/documents')->assertOk()->assertJsonCount(0, 'data');
        $this->withToken($token)->putJson("/api/v1/me/documents/{$document->id}", ['type' => 'passport', 'expiration_date' => now()->addYears(2)->toDateString()])->assertNotFound();
        $this->withToken($token)->deleteJson("/api/v1/me/documents/{$document->id}")->assertNotFound();
        $this->withToken($token)->getJson('/api/v1/favorites')->assertOk()->assertJsonMissing(['note' => 'Note privée']);
        $this->withToken($token)->patchJson("/api/v1/favorites/{$demarche->id}/note", ['note' => 'piratée'])->assertNotFound();
        $this->withToken($token)->deleteJson("/api/v1/favorites/{$demarche->id}")->assertNotFound();

        $this->assertTrue($document->fresh()->expiration_date->isSameDay(now()->addYear()));
        $this->assertSame('Note privée', $owner->favorites()->first()->note);
    }

    public function test_guessing_the_current_password_with_a_stolen_token_is_rate_limited(): void
    {
        $user = $this->citizen('guess@matrix.test');
        $token = $user->createToken('test')->plainTextToken;
        $body = ['current_password' => 'Mauvais123!', 'password' => 'Nouveau123!', 'password_confirmation' => 'Nouveau123!'];

        for ($i = 0; $i < 5; $i++) {
            $this->withToken($token)->postJson('/api/v1/auth/change-password', $body)->assertStatus(422);
        }
        $this->withToken($token)->postJson('/api/v1/auth/change-password', $body)->assertStatus(429);
        $this->assertTrue(Hash::check('Secret123!', $user->fresh()->password));
    }
}
