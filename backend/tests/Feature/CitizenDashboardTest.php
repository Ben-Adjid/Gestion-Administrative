<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\Demarche;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Tableau de bord citoyen : compteurs, alertes d'expiration et démarches sauvegardées, uniquement pour le citoyen connecté. */
class CitizenDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function demarche(string $slug, string $status = 'published'): Demarche
    {
        $categorie = Categorie::firstOrCreate(['slug' => 'identite'], ['name' => 'Identité', 'is_active' => true]);

        return Demarche::create([
            'categorie_id' => $categorie->id, 'title' => ucfirst(str_replace('-', ' ', $slug)), 'slug' => $slug,
            'summary' => 'Résumé', 'description' => 'Description', 'cost' => '5 000 FCFA', 'cost_amount' => 5000,
            'institution' => 'Mairie', 'location' => 'Mairie', 'processing_time' => '2 semaines', 'validity' => '10 ans',
            'required_documents' => ['Photo'], 'conditions' => ['Être majeur'], 'procedure_steps' => ['Déposer le dossier'],
            'status' => $status, 'published_at' => $status === 'published' ? now() : null,
        ]);
    }

    public function test_dashboard_reports_counters_alerts_and_saved_procedures(): void
    {
        $citizen = User::create(['name' => 'Aïcha', 'email' => 'aicha@test.td', 'password' => Hash::make('Secret123!')]);
        $citizen->documents()->create(['type' => 'cni', 'label' => 'CNI familiale', 'issue_date' => now()->subYears(10)->addDay(), 'expiration_date' => now()->subDays(20)->toDateString()]);
        $citizen->documents()->create(['type' => 'passeport', 'label' => 'Passeport', 'issue_date' => now()->subYears(2), 'expiration_date' => now()->addMonths(2)->toDateString()]);
        $citizen->favorites()->create(['demarche_id' => $this->demarche('passeport-biometrique')->id]);
        // Une fiche encore en brouillon n'est jamais affichée au citoyen.
        $citizen->favorites()->create(['demarche_id' => $this->demarche('fiche-en-brouillon', 'draft')->id]);

        $this->actingAs($citizen, 'sanctum')->getJson('/api/v1/me/dashboard?lang=fr')
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Aïcha')
            ->assertJsonPath('data.counters.documents', 2)
            ->assertJsonPath('data.counters.alerts', 1)
            ->assertJsonPath('data.counters.expiring_soon', 1)
            ->assertJsonPath('data.counters.favorites', 1)
            ->assertJsonCount(2, 'data.alerts')
            ->assertJsonCount(1, 'data.recent_favorites');
    }

    public function test_citizen_dashboard_is_not_available_to_anonymous_visitors(): void
    {
        $this->getJson('/api/v1/me/dashboard')->assertStatus(401);
    }
}
