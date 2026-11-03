<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\GeoUnit;
use App\Models\Categorie;
use App\Models\Demarche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Garde-fous qualité : pas de publication ni de vérification sans source, tableau de bord qualité. */
class CatalogueQualityGateTest extends TestCase
{
    use RefreshDatabase;

    private Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categorie = Categorie::create(['name' => 'Identité', 'slug' => 'identite', 'is_active' => true]);
    }

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Admin', 'email' => 'admin@quality.test', 'password' => Hash::make('Secret123!'), 'role' => 'admin', 'is_active' => true,
        ]);
    }

    private function demarche(string $slug, array $attributes = []): Demarche
    {
        return Demarche::create($attributes + [
            'categorie_id' => $this->categorie->id, 'title' => $slug, 'slug' => $slug, 'description' => 'd',
            'institution' => 'Institution', 'status' => 'draft', 'required_documents' => ['Photo'],
            'conditions' => ['Règle 1', 'Règle 2', 'Règle 3', 'Règle 4'],
        ]);
    }

    public function test_publishing_is_refused_without_an_official_source(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $demarche = $this->demarche('sans-source', ['last_verified_at' => now()]);

        $this->patchJson("/api/v1/admin/demarches/{$demarche->id}/publish")
            ->assertUnprocessable()->assertJsonPath('message', 'Publication refusée : aucune source officielle renseignée pour cette fiche.');
        $this->assertSame('draft', $demarche->fresh()->status);
    }

    public function test_publishing_is_refused_until_the_fiche_is_verified(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $demarche = $this->demarche('non-verifiee', ['official_source_url' => 'https://example.test/source']);

        $this->patchJson("/api/v1/admin/demarches/{$demarche->id}/publish")->assertUnprocessable();
        $this->assertSame('draft', $demarche->fresh()->status);
        $this->assertNull($demarche->fresh()->last_verified_at, 'Publier ne doit jamais dater une vérification.');
    }

    public function test_verification_is_refused_without_a_source(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $demarche = $this->demarche('verif-sans-source');

        $this->patchJson("/api/v1/admin/demarches/{$demarche->id}/verify")->assertUnprocessable();
        $this->assertNull($demarche->fresh()->last_verified_at);
    }

    public function test_quality_dashboard_counts_gaps_and_lists_fiches_to_fix(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        // Publiée sans source ni vérification (héritage) : deux anomalies.
        $this->demarche('publiee-brute', ['status' => 'published']);
        // Publiée, sourcée, vérifiée il y a un an, avec 2 règles seulement : périmée et sous le minimum.
        $this->demarche('publiee-perimee', [
            'status' => 'published', 'official_source_url' => 'https://example.test/s', 'last_verified_at' => now()->subYear(),
            'conditions' => ['Une', 'Deux'],
        ]);
        // Brouillon complet : prêt à publier.
        $this->demarche('brouillon-pret', ['official_source_url' => 'https://example.test/s', 'last_verified_at' => now()]);

        $data = $this->getJson('/api/v1/admin/quality')->assertOk()->json('data');

        $this->assertSame(2, $data['published']);
        $this->assertSame(1, $data['published_without_source']);
        $this->assertSame(1, $data['published_never_verified']);
        $this->assertSame(1, $data['published_stale_verification']);
        $this->assertSame(1, $data['published_under_min_rules']);
        $this->assertSame(1, $data['drafts']);
        $this->assertSame(1, $data['drafts_ready_to_publish']);

        $issues = collect($this->getJson('/api/v1/admin/quality')->json('items'))->keyBy('slug');
        $this->assertContains('sans_source', $issues['publiee-brute']['issues']);
        $this->assertContains('non_verifiee', $issues['publiee-brute']['issues']);
        $this->assertContains('verification_perimee', $issues['publiee-perimee']['issues']);
        $this->assertContains('moins_de_4_regles', $issues['publiee-perimee']['issues']);
    }

    public function test_updating_a_fiche_to_published_obeys_the_same_gate(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $demarche = $this->demarche('maj-sans-garde');

        $this->putJson("/api/v1/admin/demarches/{$demarche->id}", ['status' => 'published'])->assertUnprocessable();
        $this->assertSame('draft', $demarche->fresh()->status);
        $this->assertNull($demarche->fresh()->last_verified_at);
    }

    public function test_draft_checklist_lists_what_is_missing_before_publication(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $this->demarche('brouillon-incomplet', ['cost' => 'À renseigner', 'institution' => 'À renseigner', 'conditions' => []]);
        $this->demarche('brouillon-complet', ['official_source_url' => 'https://example.test/s', 'last_verified_at' => now(), 'cost' => '5 000 FCFA', 'processing_time' => '2 semaines', 'procedure_steps' => ['Étape 1']]);

        $body = $this->getJson('/api/v1/admin/quality/drafts')->assertOk()->json();

        $this->assertSame(2, $body['total']);
        $this->assertSame(1, $body['ready']);
        $rows = collect($body['data'])->keyBy('title');
        $this->assertFalse($rows['brouillon-incomplet']['ready']);
        $this->assertContains('cout', $rows['brouillon-incomplet']['missing']);
        $this->assertContains('regles', $rows['brouillon-incomplet']['missing']);
        $this->assertTrue($rows['brouillon-complet']['ready']);
    }

    public function test_the_editor_of_a_fiche_cannot_publish_it_alone(): void
    {
        $author = $this->admin();
        $this->actingAs($author, 'sanctum');
        $demarche = $this->demarche('double-validation', ['official_source_url' => 'https://example.test/s', 'last_verified_at' => now()]);
        $this->putJson("/api/v1/admin/demarches/{$demarche->id}", ['cost' => '3 000 FCFA'])->assertOk();

        $this->patchJson("/api/v1/admin/demarches/{$demarche->id}/publish")->assertForbidden();
        $this->putJson("/api/v1/admin/demarches/{$demarche->id}", ['status' => 'published'])->assertForbidden();

        $reviewer = Admin::create(['name' => 'Relecteur', 'email' => 'reviewer@quality.test', 'password' => Hash::make('Secret123!'), 'role' => 'admin', 'is_active' => true]);
        $this->actingAs($reviewer, 'sanctum');
        $this->patchJson("/api/v1/admin/demarches/{$demarche->id}/publish")->assertOk();
        $this->assertSame('published', $demarche->fresh()->status);
    }

    public function test_a_fiche_can_be_restored_to_a_previous_version_without_changing_its_status(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $demarche = $this->demarche('retour-arriere', [
            'cost' => '5 000 FCFA', 'official_source_url' => 'https://example.test/s', 'last_verified_at' => now(),
            'required_documents' => ['Photo', 'Acte'],
        ]);
        $this->putJson("/api/v1/admin/demarches/{$demarche->id}", ['cost' => '9 000 FCFA', 'required_documents' => ['Photo']])->assertOk();

        $history = $this->getJson("/api/v1/admin/demarches/{$demarche->id}/history")->assertOk()->json('data');
        $this->assertNotEmpty($history);
        $entry = $history[0]['id'];

        $this->postJson("/api/v1/admin/demarches/{$demarche->id}/restore/{$entry}")->assertOk();
        $fresh = $demarche->fresh();
        $this->assertSame('5 000 FCFA', $fresh->cost);
        $this->assertSame(['Photo', 'Acte'], $fresh->required_documents);
        $this->assertSame('draft', $fresh->status, 'Le retour arrière ne publie ni ne dépublie jamais.');
        $this->assertTrue(ActivityLog::where('action', 'restore_demarche')->where('subject_id', $demarche->id)->exists());
    }

    public function test_geo_import_refuses_the_whole_batch_when_one_line_is_wrong(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $rows = [
            ['level' => 'province', 'name' => 'Batha', 'status' => 'to_verify'],
            ['level' => 'province', 'name' => 'Sila', 'status' => 'official'],
        ];

        $preview = $this->postJson('/api/v1/admin/geo/import/preview', ['rows' => $rows])->assertOk();
        $this->assertSame(1, $preview->json('errors'));

        $this->postJson('/api/v1/admin/geo/import/commit', ['rows' => $rows])->assertUnprocessable();
        $this->assertDatabaseMissing('geo_units', ['name' => 'Batha']);
    }

    public function test_geo_import_creates_valid_rows_and_flags_duplicates(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $province = GeoUnit::create(['level' => 'province', 'name' => 'Salamat', 'slug' => 'salamat', 'status' => 'to_verify', 'is_active' => true]);
        $rows = [
            ['level' => 'department', 'parent_slug' => 'salamat', 'name' => 'Département A', 'status' => 'to_verify'],
            ['level' => 'department', 'parent_slug' => 'salamat', 'name' => 'Département A', 'status' => 'to_verify'],
        ];

        $preview = $this->postJson('/api/v1/admin/geo/import/preview', ['rows' => $rows])->assertOk();
        $this->assertSame(1, $preview->json('errors'), 'Le doublon dans le même fichier doit être signalé.');

        $ok = [['level' => 'department', 'parent_slug' => 'salamat', 'name' => 'Département A', 'status' => 'to_verify']];
        $this->postJson('/api/v1/admin/geo/import/commit', ['rows' => $ok])->assertOk()->assertJsonPath('created', 1);
        $this->assertDatabaseHas('geo_units', ['name' => 'Département A', 'parent_id' => $province->id]);
        $this->postJson('/api/v1/admin/geo/import/commit', ['rows' => $ok])->assertUnprocessable();
    }

    public function test_quality_dashboard_is_reserved_to_staff(): void
    {
        $this->getJson('/api/v1/admin/quality')->assertUnauthorized();
    }
}
