<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Categorie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): Admin
    {
        return Admin::create([
            'name' => 'Admin',
            'email' => "{$role}@features.test",
            'password' => Hash::make('Ancien-Mot2Passe!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_admin_changes_own_password_and_other_sessions_are_revoked(): void
    {
        $admin = $this->admin();
        $admin->createToken('autre-appareil');
        $this->actingAs($admin, 'sanctum');

        $this->postJson('/api/v1/admin/auth/change-password', [
            'current_password' => 'mauvais',
            'password' => 'Nouveau-Mot2Passe!',
            'password_confirmation' => 'Nouveau-Mot2Passe!',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->postJson('/api/v1/admin/auth/change-password', [
            'current_password' => 'Ancien-Mot2Passe!',
            'password' => 'Nouveau-Mot2Passe!',
            'password_confirmation' => 'Nouveau-Mot2Passe!',
        ])->assertOk();

        $this->assertTrue(Hash::check('Nouveau-Mot2Passe!', $admin->fresh()->password));
        $this->assertSame(0, $admin->tokens()->count());
        $this->assertDatabaseHas('activity_logs', ['admin_id' => $admin->id, 'action' => 'admin_password_changed']);
    }

    public function test_admin_saves_demarche_translations_and_public_api_serves_them(): void
    {
        $categorie = Categorie::create(['name' => 'Identité', 'slug' => 'identite', 'is_active' => true]);
        $this->actingAs($this->admin(), 'sanctum');

        $id = $this->postJson('/api/v1/admin/demarches', [
            'title' => 'Légalisation de signature',
            'description' => 'Faire légaliser une signature.',
            'categorie_id' => $categorie->id,
            'cost' => '500 FCFA',
            'institution' => 'Mairie',
            'validity' => 'Permanente',
            'status' => 'published',
            'title_en' => 'Signature legalisation',
            'validity_en' => 'Permanent',
            'procedure_steps_en' => ['Go to the town hall'],
            'title_ar' => 'تصديق التوقيع',
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/v1/admin/demarches/{$id}")
            ->assertOk()
            ->assertJsonPath('data.translations.en.title', 'Signature legalisation')
            ->assertJsonPath('data.translations.ar.title', 'تصديق التوقيع');

        $this->putJson("/api/v1/admin/demarches/{$id}", ['title_ar' => 'تصديق الإمضاء'])->assertOk();

        $this->getJson('/api/v1/demarches/legalisation-de-signature?lang=ar')
            ->assertOk()
            ->assertJsonPath('data.title', 'تصديق الإمضاء');
        $this->getJson('/api/v1/demarches/legalisation-de-signature?lang=en')
            ->assertOk()
            ->assertJsonPath('data.validity', 'Permanent')
            ->assertJsonPath('data.procedure_steps.0', 'Go to the town hall');
    }

    public function test_admin_session_expires_after_inactivity_not_while_working(): void
    {
        $admin = $this->admin();
        $token = $this->postJson('/api/v1/admin/auth/login', ['email' => $admin->email, 'password' => 'Ancien-Mot2Passe!'])
            ->assertOk()->json('token');
        $headers = ['Authorization' => "Bearer {$token}"];

        // 12 minutes plus tard, l'administrateur travaille encore : la session est prolongée.
        $this->travel(12)->minutes();
        $this->getJson('/api/v1/admin/auth/me', $headers)->assertOk();
        $this->app['auth']->forgetGuards();

        // 12 minutes après la dernière action (24 min après la connexion) : toujours connecté.
        $this->travel(12)->minutes();
        $this->getJson('/api/v1/admin/auth/me', $headers)->assertOk();
        $this->app['auth']->forgetGuards();

        // 16 minutes sans aucune action : déconnecté.
        $this->travel(16)->minutes();
        $this->getJson('/api/v1/admin/auth/me', $headers)->assertUnauthorized();
    }

    public function test_cost_amount_follows_displayed_cost_and_slug_stays_stable(): void
    {
        $categorie = Categorie::create(['name' => 'Identité', 'slug' => 'identite', 'is_active' => true]);
        $this->actingAs($this->admin(), 'sanctum');

        $id = $this->postJson('/api/v1/admin/demarches', [
            'title' => 'Duplicata de carte', 'description' => 'Test.', 'categorie_id' => $categorie->id,
            'cost' => '3 000 FCFA', 'cost_amount' => 999, 'institution' => 'Mairie', 'status' => 'draft',
        ])->assertCreated()->assertJsonPath('data.cost_amount', 3000)->json('data.id');

        // Le coût affiché change : le montant suit, même si l'ancien montant est renvoyé tel quel.
        $this->putJson("/api/v1/admin/demarches/{$id}", ['cost' => '3 500 FCFA', 'cost_amount' => 3000])
            ->assertOk()->assertJsonPath('data.cost_amount', 3500);
        $this->putJson("/api/v1/admin/demarches/{$id}", ['cost' => 'Gratuit'])->assertJsonPath('data.cost_amount', 0);

        // Fourchette : pas de montant unique, la valeur saisie est conservée.
        $this->putJson("/api/v1/admin/demarches/{$id}", ['cost' => '5 000 – 10 000 FCFA', 'cost_amount' => null])
            ->assertJsonPath('data.cost_amount', null);

        // Corriger le titre ne casse pas l'adresse publique de la fiche.
        $this->putJson("/api/v1/admin/demarches/{$id}", ['title' => 'Duplicata de la carte d’identité'])
            ->assertOk()->assertJsonPath('data.slug', 'duplicata-de-carte');
    }

    public function test_admin_saves_category_translations_and_actions_are_audited(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'sanctum');

        $id = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Voyage',
            'name_en' => 'Travel',
            'name_ar' => 'السفر',
        ])->assertCreated()->json('data.id');

        $this->getJson('/api/v1/categories?lang=ar')->assertOk()->assertJsonPath('data.0.name', 'السفر');
        $this->getJson("/api/v1/admin/categories/{$id}")->assertOk()->assertJsonPath('data.translations.en.name', 'Travel');
        $this->assertDatabaseHas('activity_logs', ['admin_id' => $admin->id, 'action' => 'create_category']);
    }
}
