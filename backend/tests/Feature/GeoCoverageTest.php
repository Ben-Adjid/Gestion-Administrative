<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Categorie;
use App\Models\City;
use App\Models\Demarche;
use App\Models\GeoUnit;
use App\Models\Office;
use App\Models\OfficeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Couverture géographique : référentiel, recherche, bureaux compétents et absence de redirection vers N'Djaména.
 * Les données de test sont créées ici ; aucune source externe n'est utilisée.
 */
class GeoCoverageTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, City> */
    private array $cities = [];
    /** @var array<string, Demarche> */
    private array $demarches = [];
    private Office $ndjamenaOffice;
    private Office $moundouOffice;
    private Office $bongorOffice;
    private Office $mongoOffice;

    protected function setUp(): void
    {
        parent::setUp();

        $categorie = Categorie::create(['name' => 'Identité', 'slug' => 'identite', 'is_active' => true]);
        foreach (['ndjamena' => 'N\'Djaména', 'moundou' => 'Moundou', 'bongor' => 'Bongor', 'mongo' => 'Mongo', 'ati' => 'Ati', 'sarh' => 'Sarh'] as $slug => $name) {
            $this->cities[$slug] = City::create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
        }
        foreach (['carte-didentite-nationale', 'passeport-biometrique', 'acte-de-naissance', 'casier-judiciaire'] as $slug) {
            $this->demarches[$slug] = Demarche::create([
                'categorie_id' => $categorie->id, 'title' => $slug, 'slug' => $slug, 'description' => 'd',
                'institution' => 'Institution', 'status' => 'published', 'required_documents' => ['Photo'],
            ]);
        }

        $this->ndjamenaOffice = $this->office('ndjamena', 'Centre ANATS du 1er arrondissement', 'https://example.test/ndj');
        $this->moundouOffice = $this->office('moundou', 'Bureau état civil de Moundou', 'https://example.test/mou');
        $this->bongorOffice = $this->office('bongor', 'Centre titres sécurisés de Bongor', 'https://example.test/bon');
        $this->mongoOffice = $this->office('mongo', 'Antenne ANATS de Mongo', 'https://example.test/mon');
        // Ati : bureau signalé mais aucun service rattaché (il ne doit jamais être proposé).
        $this->office('ati', 'Centre ANATS d\'Ati', 'https://example.test/ati');

        $this->service($this->ndjamenaOffice, 'carte-didentite-nationale', 'press_reported');
        $this->service($this->moundouOffice, 'acte-de-naissance', 'press_reported');
        $this->service($this->bongorOffice, 'passeport-biometrique', 'press_reported');
        $this->service($this->mongoOffice, 'passeport-biometrique', 'press_reported');
    }

    private function office(string $citySlug, string $name, string $source): Office
    {
        return Office::create(['city_id' => $this->cities[$citySlug]->id, 'name' => $name, 'is_active' => true, 'source_url' => $source]);
    }

    private function service(Office $office, string $demarcheSlug, string $status, ?string $verifiedOn = null): OfficeService
    {
        return OfficeService::create([
            'office_id' => $office->id, 'demarche_id' => $this->demarches[$demarcheSlug]->id, 'status' => $status,
            'source_label' => 'Source de test', 'source_url' => $office->source_url, 'verified_on' => $verifiedOn,
        ]);
    }

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Admin', 'email' => 'admin@geo.test', 'password' => Hash::make('Secret123!'), 'role' => 'admin', 'is_active' => true,
        ]);
    }

    private function place(string $demarcheSlug, string $citySlug): array
    {
        return $this->getJson("/api/v1/demarches/{$demarcheSlug}?city={$citySlug}&lang=fr")->assertOk()->json('place');
    }

    // ------------------------------------------------------------------ Bureaux compétents

    public function test_moundou_birth_certificate_shows_the_moundou_office_only(): void
    {
        $place = $this->place('acte-de-naissance', 'moundou');

        $this->assertCount(1, $place['offices']);
        $this->assertSame('Bureau état civil de Moundou', $place['offices'][0]['name']);
        $this->assertSame('press_reported', $place['offices'][0]['service_status']);
        $this->assertTrue($place['has_local_office']);
        // Un bureau signalé par la presse n'est jamais présenté comme lieu vérifié.
        $this->assertFalse($place['has_verified_place']);
        $this->assertSame([], $place['elsewhere_offices']);
    }

    public function test_moundou_passport_never_redirects_to_ndjamena(): void
    {
        $place = $this->place('passeport-biometrique', 'moundou');

        $this->assertSame([], $place['offices'], 'Aucun bureau de Moundou ne délivre de passeport dans les sources.');
        $this->assertFalse($place['has_local_office']);
        $this->assertFalse($place['has_verified_place']);
        // Les bureaux d'autres villes ne sont proposés qu'à titre de renseignement, compétence non confirmée.
        $cities = collect($place['elsewhere_offices'])->pluck('city.slug')->unique()->all();
        $this->assertNotContains('ndjamena', $cities);
        $this->assertNotContains('moundou', $cities);
        foreach ($place['elsewhere_offices'] as $office) {
            $this->assertSame('not_confirmed', $office['competence']);
        }
    }

    public function test_ndjamena_identity_card_lists_the_arrondissement_centres_as_press_reported(): void
    {
        $place = $this->place('carte-didentite-nationale', 'ndjamena');

        $this->assertCount(1, $place['offices']);
        $this->assertSame('press_reported', $place['offices'][0]['service_status']);
        $this->assertFalse($place['has_verified_place']);
    }

    public function test_office_without_any_service_is_never_proposed(): void
    {
        foreach (array_keys($this->demarches) as $demarche) {
            $place = $this->place($demarche, 'ati');
            $this->assertSame([], $place['offices'], $demarche);
            $this->assertNotContains('ati', collect($place['offices_available_in'])->pluck('slug')->all());
        }
    }

    public function test_unverified_service_is_not_displayed(): void
    {
        $this->service($this->mongoOffice, 'casier-judiciaire', 'to_verify');

        $place = $this->place('casier-judiciaire', 'mongo');
        $this->assertSame([], $place['offices']);
        $this->assertSame([], $place['elsewhere_offices']);
    }

    public function test_confirmed_service_with_source_gives_a_verified_place(): void
    {
        $this->service($this->ndjamenaOffice, 'passeport-biometrique', 'confirmed', now()->subDays(10)->toDateString());

        $place = $this->place('passeport-biometrique', 'ndjamena');
        $this->assertTrue($place['has_verified_place']);
        $this->assertSame('confirmed', $place['offices'][0]['service_status']);
        $this->assertSame(now()->subDays(10)->toDateString(), $place['offices'][0]['service_verified_on']);
    }

    public function test_national_coverage_never_leaks_offices_between_cities(): void
    {
        // Toutes les localités × toutes les démarches configurées.
        foreach (array_keys($this->cities) as $citySlug) {
            foreach (array_keys($this->demarches) as $demarcheSlug) {
                $place = $this->place($demarcheSlug, $citySlug);

                foreach ($place['offices'] as $office) {
                    $this->assertSame($citySlug, $office['city']['slug'], "{$demarcheSlug} à {$citySlug} : bureau d'une autre ville");
                }
                foreach ($place['elsewhere_offices'] as $office) {
                    $this->assertNotSame($citySlug, $office['city']['slug'], "{$demarcheSlug} à {$citySlug} : bureau local listé comme lointain");
                    $this->assertSame('not_confirmed', $office['competence']);
                }
                if ($citySlug !== 'ndjamena') {
                    $this->assertNotContains('ndjamena', collect($place['offices'])->pluck('city.slug')->all(), "{$demarcheSlug} à {$citySlug} redirigée vers N'Djaména");
                }
                $this->assertSame($citySlug, $place['city']['slug']);
            }
        }
    }

    // ------------------------------------------------------------------ Référentiel géographique

    public function test_geo_tree_and_search_follow_the_hierarchy(): void
    {
        $province = GeoUnit::create(['level' => 'province', 'name' => 'Logone Occidental', 'slug' => 'logone-occidental', 'status' => 'to_verify', 'is_active' => true]);
        GeoUnit::create(['parent_id' => $province->id, 'level' => 'locality', 'name' => 'Moundou', 'slug' => 'moundou-test', 'status' => 'to_verify', 'is_active' => true]);

        $tree = $this->getJson('/api/v1/geo/tree')->assertOk();
        $this->assertSame('Logone Occidental', $tree->json('data.0.name'));
        $this->assertSame('Moundou', $tree->json('data.0.children.0.name'));
        $this->assertSame(1, $tree->json('counts.provinces'));

        $found = $this->getJson('/api/v1/geo/search?q=mou')->assertOk()->json('data');
        $this->assertSame('Logone Occidental › Moundou', $found[0]['path']);

        $this->getJson('/api/v1/geo/search?q=introuvable-xyz')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_geo_search_restricts_to_children_of_a_parent(): void
    {
        $province = GeoUnit::create(['level' => 'province', 'name' => 'Batha', 'slug' => 'batha', 'status' => 'to_verify', 'is_active' => true]);
        GeoUnit::create(['parent_id' => $province->id, 'level' => 'locality', 'name' => 'Ati', 'slug' => 'ati-test', 'status' => 'to_verify', 'is_active' => true]);
        GeoUnit::create(['level' => 'province', 'name' => 'Autre', 'slug' => 'autre', 'status' => 'to_verify', 'is_active' => true]);

        $inBatha = $this->getJson('/api/v1/geo/search?parent=batha')->assertOk()->json('data');
        $this->assertSame(['Ati'], collect($inBatha)->pluck('name')->all());
    }

    public function test_admin_cannot_mark_a_geo_unit_official_without_source(): void
    {
        $this->actingAs($this->admin(), 'sanctum');

        $this->postJson('/api/v1/admin/geo/units', ['level' => 'province', 'name' => 'Sila', 'status' => 'official'])
            ->assertUnprocessable()->assertJsonValidationErrors(['status']);

        $this->postJson('/api/v1/admin/geo/units', [
            'level' => 'province', 'name' => 'Sila', 'status' => 'official',
            'source_url' => 'https://example.test/ordonnance', 'verified_on' => '2026-10-20',
        ])->assertCreated()->assertJsonPath('data.status', 'official');
        $this->assertDatabaseHas('activity_logs', ['action' => 'create_geo_unit']);
    }

    public function test_locality_must_sit_under_a_compatible_parent(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $province = GeoUnit::create(['level' => 'province', 'name' => 'Salamat', 'slug' => 'salamat', 'status' => 'to_verify', 'is_active' => true]);

        // Une province n'a pas de parent ; un département doit avoir une province pour parent.
        $base = ['status' => 'to_verify'];
        $this->postJson('/api/v1/admin/geo/units', $base + ['level' => 'province', 'parent_id' => $province->id, 'name' => 'X'])->assertUnprocessable()->assertJsonValidationErrors(['parent_id']);
        $this->postJson('/api/v1/admin/geo/units', $base + ['level' => 'department', 'parent_id' => null, 'name' => 'Dép'])->assertUnprocessable()->assertJsonValidationErrors(['parent_id']);
        $this->postJson('/api/v1/admin/geo/units', $base + ['level' => 'commune', 'parent_id' => $province->id, 'name' => 'Com'])->assertUnprocessable()->assertJsonValidationErrors(['parent_id']);
        $this->postJson('/api/v1/admin/geo/units', $base + ['level' => 'department', 'parent_id' => $province->id, 'name' => 'Dép'])->assertCreated();
    }

    // ------------------------------------------------------------------ Services des bureaux

    public function test_confirmed_service_requires_source_and_verification_date(): void
    {
        $this->actingAs($this->admin(), 'sanctum');

        $this->postJson('/api/v1/admin/geo/services', [
            'office_id' => $this->mongoOffice->id, 'demarche_id' => $this->demarches['casier-judiciaire']->id, 'status' => 'confirmed',
        ])->assertUnprocessable()->assertJsonValidationErrors(['source_url']);

        $this->postJson('/api/v1/admin/geo/services', [
            'office_id' => $this->mongoOffice->id, 'demarche_id' => $this->demarches['casier-judiciaire']->id, 'status' => 'confirmed',
            'source_url' => 'https://example.test/officiel',
        ])->assertUnprocessable()->assertJsonValidationErrors(['verified_on']);
    }

    public function test_removing_a_service_leaves_a_trace_in_the_activity_log(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $service = OfficeService::where('office_id', $this->bongorOffice->id)->firstOrFail();

        $this->deleteJson("/api/v1/admin/geo/services/{$service->id}")->assertOk();

        $this->assertDatabaseMissing('office_services', ['id' => $service->id]);
        $this->assertTrue(ActivityLog::where('action', 'delete_office_service')->where('subject_id', $service->id)->exists());
    }

    public function test_public_visitors_cannot_write_geo_data(): void
    {
        $this->postJson('/api/v1/admin/geo/units', ['level' => 'province', 'name' => 'Intrus', 'status' => 'to_verify'])
            ->assertUnauthorized();
        $this->assertDatabaseMissing('geo_units', ['name' => 'Intrus']);
    }
}
