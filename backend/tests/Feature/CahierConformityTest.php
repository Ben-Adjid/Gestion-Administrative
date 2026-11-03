<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\Demarche;
use Database\Seeders\CahierDemarchesSeeder;
use Database\Seeders\CahierPhase2DraftsSeeder;
use Database\Seeders\CahierProposalsDraftsSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Conformité au cahier des charges v2.0 : 80 démarches publiées, 10 catégories, pièces et étapes présentes.
 * Les fiches n'ont pas de source officielle ni de date de vérification : le test le constate sans le masquer.
 */
class CahierConformityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cahier_lists_eighty_procedures_in_ten_categories(): void
    {
        $records = json_decode(file_get_contents(database_path('data/cahier_demarches.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(80, $records);
        $this->assertCount(10, array_unique(array_column($records, 'category')));
    }

    /** Les dix catégories du cahier, créées comme le fait la normalisation du catalogue. */
    private function seedCategories(): void
    {
        $records = json_decode(file_get_contents(database_path('data/cahier_demarches.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach (array_unique(array_column($records, 'category')) as $slug) {
            Categorie::create(['name' => $slug, 'slug' => $slug, 'is_active' => true]);
        }
    }

    public function test_every_seeded_procedure_has_documents_steps_cost_and_delay(): void
    {
        $this->seedCategories();
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(CahierDemarchesSeeder::class);

        $published = Demarche::where('status', 'published')->get();
        $this->assertCount(80, $published);

        foreach ($published as $demarche) {
            $this->assertNotEmpty($demarche->required_documents, $demarche->slug);
            $this->assertNotEmpty($demarche->procedure_steps, $demarche->slug);
            $this->assertNotEmpty($demarche->cost, $demarche->slug);
            $this->assertNotEmpty($demarche->processing_time, $demarche->slug);
        }
    }

    public function test_the_ten_cahier_categories_hold_the_published_procedures(): void
    {
        $this->seedCategories();
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(CahierDemarchesSeeder::class);

        $used = Demarche::where('status', 'published')->with('categorie')->get()->pluck('categorie.slug')->unique()->count();
        $this->assertSame(10, $used);
    }

    public function test_proposed_procedures_are_unpublished_drafts_with_their_status(): void
    {
        $this->seedCategories();
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(CahierDemarchesSeeder::class);
        $this->seed(CahierProposalsDraftsSeeder::class);

        $this->assertCount(8, Demarche::where('status', 'draft')->get());
        $this->assertCount(0, Demarche::where('status', 'published')->where('institution', 'À renseigner')->get());
        // Trois propositions ont une source partielle (célibat, changement d'adresse, copie de jugement).
        $this->assertCount(3, Demarche::where('status', 'draft')->where('description', 'like', '%Statut de vérification : partiel%')->get());
    }

    public function test_phase_two_procedures_stay_unpublished_drafts(): void
    {
        $this->seedCategories();
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(CahierDemarchesSeeder::class);
        $this->seed(CahierPhase2DraftsSeeder::class);

        $drafts = Demarche::where('status', 'draft')->get();
        $this->assertCount(12, $drafts);
        $this->assertCount(80, Demarche::where('status', 'published')->get());
        foreach ($drafts as $draft) {
            $this->assertFalse((bool) $draft->is_online, $draft->slug);
            $this->assertStringContainsString('consulté le 03/11/2026', $draft->description, $draft->slug);
        }
    }
}
