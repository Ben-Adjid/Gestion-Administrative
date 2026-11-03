<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use App\Models\Categorie;
use App\Models\Demarche;
use App\Models\Signalement;
use App\Mail\SignalementResponseMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Database\Seeders\DatabaseSeeder;

class AdminAuthAndCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_seeder_creates_only_the_single_super_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('admins', 1);
        $this->assertDatabaseHas('admins', [
            'email' => 'superadmin@demarches.td',
            'role' => 'super_admin',
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_login_and_receive_token(): void
    {
        $admin = Admin::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.td',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@test.td',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'admin' => ['id', 'email', 'role']]);
    }

    public function test_unauthenticated_user_cannot_access_admin_endpoints(): void
    {
        $response = $this->getJson('/api/v1/admin/demarches');
        $response->assertStatus(401);
    }

    public function test_editor_cannot_access_super_admin_activity_logs(): void
    {
        $editor = Admin::create([
            'name' => 'Editeur Test',
            'email' => 'editor@test.td',
            'password' => Hash::make('Secret123!'),
            'role' => 'editor',
            'is_active' => true,
        ]);

        $this->actingAs($editor, 'sanctum');

        $response = $this->getJson('/api/v1/admin/activity-logs');
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_activity_logs(): void
    {
        $superAdmin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@test.td',
            'password' => Hash::make('Secret123!'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin, 'sanctum');

        $response = $this->getJson('/api/v1/admin/activity-logs');
        $response->assertStatus(200);
    }

    public function test_only_super_admin_can_view_anonymous_traffic_statistics(): void
    {
        $this->getJson('/api/v1/admin/analytics')->assertUnauthorized();

        $superAdmin = Admin::create([
            'name' => 'Super Admin Analytics',
            'email' => 'analytics@test.td',
            'password' => Hash::make('Secret123!'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $this->actingAs($superAdmin, 'sanctum');

        $visitorId = '9f4d5e7a-1b2c-4d6e-8f90-123456789abc';
        $this->postJson('/api/v1/analytics/page-views', [
            'visitor_id' => $visitorId,
            'path' => '/demarches',
        ])->assertAccepted();

        $this->getJson('/api/v1/admin/analytics')
            ->assertOk()
            ->assertJsonPath('active_sessions', 1)
            ->assertJsonPath('sessions_today', 1)
            ->assertJsonPath('views_today', 1)
            ->assertJsonPath('top_pages.0.path', '/demarches');
    }

    public function test_admin_can_create_archive_and_restore_a_demarche(): void
    {
        $admin = Admin::create([
            'name' => 'Admin CRUD',
            'email' => 'crud@test.td',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $category = Categorie::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $this->actingAs($admin, 'sanctum');

        $created = $this->postJson('/api/v1/admin/demarches', [
            'categorie_id' => $category->id,
            'title' => 'Démarche test',
            'description' => 'Description de la démarche de test.',
            'cost' => 'Gratuit',
            'institution' => 'Administration test',
            'status' => 'draft',
        ])->assertCreated();

        $id = $created->json('data.id');
        $this->deleteJson("/api/v1/admin/demarches/{$id}")->assertOk();
        $this->assertSoftDeleted('demarches', ['id' => $id]);
        $this->getJson('/api/v1/admin/demarches?trashed=only')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->postJson("/api/v1/admin/demarches/{$id}/restore")->assertOk();
        $this->assertDatabaseHas('demarches', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_admin_can_update_publish_and_filter_a_demarche(): void
    {
        $admin = Admin::create([
            'name' => 'Admin Démarches',
            'email' => 'demarches@test.td',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $category = Categorie::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $this->actingAs($admin, 'sanctum');

        $created = $this->postJson('/api/v1/admin/demarches', [
            'categorie_id' => $category->id,
            'title' => 'Démarche à modifier',
            'description' => 'Description de la démarche.',
            'cost' => 'Gratuit',
            'institution' => 'Administration test',
            'status' => 'draft',
        ])->assertCreated();
        $id = $created->json('data.id');

        $this->putJson("/api/v1/admin/demarches/{$id}", [
            'title' => 'Démarche mise à jour',
            'description' => 'Description actualisée.',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Démarche mise à jour')
            // L'adresse publique reste stable : les liens déjà partagés continuent de fonctionner.
            ->assertJsonPath('data.slug', 'demarche-a-modifier');

        // Publier exige une source officielle et une vérification réelle.
        $this->putJson("/api/v1/admin/demarches/{$id}", ['official_source_url' => 'https://example.test/source-officielle'])->assertOk();
        $this->patchJson("/api/v1/admin/demarches/{$id}/verify")->assertOk();
        // Double validation : la publication revient à un autre administrateur que celui qui a modifié la fiche.
        $other = Admin::create(['name' => 'Relecteur', 'email' => 'relecteur@test.td', 'password' => Hash::make('Secret123!'), 'role' => 'admin', 'is_active' => true]);
        $this->actingAs($other, 'sanctum');
        $this->patchJson("/api/v1/admin/demarches/{$id}/publish")->assertOk()
            ->assertJsonPath('data.status', 'published');
        $this->getJson('/api/v1/admin/demarches?status=published')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);
    }

    public function test_admin_can_create_update_and_delete_a_category(): void
    {
        $admin = Admin::create([
            'name' => 'Admin Categories',
            'email' => 'categories@test.td',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->actingAs($admin, 'sanctum');

        $created = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Services sociaux',
            'description' => 'Accompagnement des citoyens.',
            'icon' => 'heart',
            'color' => '#2563EB',
            'is_active' => true,
        ])->assertCreated();

        $id = $created->json('data.id');
        $this->assertDatabaseHas('categories', [
            'id' => $id,
            'slug' => 'services-sociaux',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $this->putJson("/api/v1/admin/categories/{$id}", [
            'name' => 'Services citoyens',
            'is_active' => false,
        ])->assertOk();
        $this->assertDatabaseHas('categories', [
            'id' => $id,
            'slug' => 'services-citoyens',
            'is_active' => false,
        ]);

        $this->deleteJson("/api/v1/admin/categories/{$id}")->assertOk();
        $this->assertSoftDeleted('categories', ['id' => $id]);
    }

    public function test_admin_can_respond_to_and_resolve_a_signalement(): void
    {
        Mail::fake();
        $admin = Admin::create([
            'name' => 'Admin Signalements',
            'email' => 'signalements@test.td',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $signalement = Signalement::create([
            'type' => 'info_erronee',
            'message' => 'Le document demandé au guichet diffère de la liste publiée.',
            'ip_address' => '127.0.0.1',
            'author_email' => 'citoyen@test.td',
            'status' => 'pending',
        ]);
        $this->actingAs($admin, 'sanctum');

        $this->patchJson("/api/v1/admin/signalements/{$signalement->id}/status", [
            'status' => 'investigating',
            'admin_notes' => 'Nous vérifions cette information auprès du service concerné.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'investigating');
        Mail::assertQueued(SignalementResponseMail::class, fn (SignalementResponseMail $mail) =>
            $mail->response === 'Nous vérifions cette information auprès du service concerné.'
        );

        $this->patchJson("/api/v1/admin/signalements/{$signalement->id}/status", [
            'status' => 'resolved',
        ])->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertDatabaseHas('signalements', [
            'id' => $signalement->id,
            'status' => 'resolved',
            'admin_notes' => 'Nous vérifions cette information auprès du service concerné.',
            'resolved_by' => $admin->id,
        ]);
    }

}
