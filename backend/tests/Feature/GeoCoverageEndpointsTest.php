<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Categorie;
use App\Models\City;
use App\Models\Demarche;
use App\Models\GeoSuggestion;
use App\Models\GeoUnit;
use App\Models\Office;
use App\Models\OfficeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Couverture publique, signalements de localités et leur traitement par l'administration. */
class GeoCoverageEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Admin', 'email' => 'admin@coverage.test', 'password' => Hash::make('Secret123!'), 'role' => 'admin', 'is_active' => true,
        ]);
    }

    /** Une province, une localité rattachée à une ville, une localité sans ville, un bureau et un service « presse ». */
    private function seedCoverage(): void
    {
        $province = GeoUnit::create(['level' => 'province', 'name' => 'Ville de N’Djaména', 'slug' => 'ville-ndjamena', 'status' => 'to_verify', 'is_active' => true]);
        $locality = GeoUnit::create(['parent_id' => $province->id, 'level' => 'locality', 'name' => 'N’Djaména', 'slug' => 'ndjamena', 'status' => 'to_verify', 'is_active' => true]);
        GeoUnit::create(['parent_id' => $province->id, 'level' => 'locality', 'name' => 'Sans ville', 'slug' => 'sans-ville', 'status' => 'to_verify', 'is_active' => true]);

        $city = City::create(['name' => 'N’Djaména', 'slug' => 'ndjamena', 'is_active' => true, 'geo_unit_id' => $locality->id]);
        $categorie = Categorie::create(['name' => 'Identité', 'slug' => 'identite', 'is_active' => true]);
        $demarche = Demarche::create([
            'categorie_id' => $categorie->id, 'title' => 'CNI', 'slug' => 'carte-didentite-nationale', 'description' => 'd',
            'institution' => 'ANATS', 'status' => 'published', 'required_documents' => ['Photo'],
        ]);
        $office = Office::create(['city_id' => $city->id, 'name' => 'Centre 1er', 'is_active' => true, 'source_url' => 'https://example.test/c']);
        OfficeService::create(['office_id' => $office->id, 'demarche_id' => $demarche->id, 'status' => 'press_reported', 'source_url' => 'https://example.test/c']);
    }

    public function test_coverage_counts_localities_offices_and_demarches_separately(): void
    {
        $this->seedCoverage();

        $response = $this->getJson('/api/v1/geo/coverage')->assertOk();

        $this->assertSame(2, $response->json('totals.localities'));
        $this->assertSame(1, $response->json('totals.localities_with_attested_office'));
        $this->assertSame(1, $response->json('totals.localities_without_attested_office'));
        $this->assertSame(1, $response->json('totals.offices_attested'));
        $this->assertSame(1, $response->json('totals.demarches_documented'));
        $this->assertSame(1, $response->json('totals.services_press_reported'));
        $this->assertSame(0, $response->json('totals.services_confirmed'));

        $rows = collect($response->json('data'))->keyBy('slug');
        $this->assertSame(1, $rows['ndjamena']['offices_attested']);
        $this->assertSame(0, $rows['sans-ville']['offices_attested']);
        $this->assertFalse($rows['sans-ville']['selectable']);
    }

    public function test_visitor_can_suggest_a_missing_locality_without_creating_it(): void
    {
        $this->postJson('/api/v1/geo/suggestions', ['name' => 'Bokoro', 'province' => 'Hadjer-Lamis'])
            ->assertCreated();

        $this->assertDatabaseHas('geo_suggestions', ['name' => 'Bokoro', 'status' => 'new']);
        $this->assertDatabaseMissing('geo_units', ['name' => 'Bokoro']);
    }

    public function test_repeated_pending_suggestion_is_not_stored_twice(): void
    {
        $this->postJson('/api/v1/geo/suggestions', ['name' => 'Bokoro'])->assertCreated();
        $this->postJson('/api/v1/geo/suggestions', ['name' => 'bokoro'])->assertCreated();

        $this->assertSame(1, GeoSuggestion::whereRaw('LOWER(name) = ?', ['bokoro'])->count());
    }

    public function test_suggestion_requires_a_real_name_and_a_valid_email(): void
    {
        $this->postJson('/api/v1/geo/suggestions', ['name' => 'X'])->assertUnprocessable()->assertJsonValidationErrors(['name']);
        $this->postJson('/api/v1/geo/suggestions', ['name' => 'Bokoro', 'contact_email' => 'pas-un-email'])
            ->assertUnprocessable()->assertJsonValidationErrors(['contact_email']);
    }

    public function test_accepting_a_suggestion_does_not_add_the_locality(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $suggestion = GeoSuggestion::create(['name' => 'Bokoro', 'status' => 'new']);

        $this->putJson("/api/v1/admin/geo/suggestions/{$suggestion->id}", ['status' => 'accepted', 'admin_note' => 'À vérifier auprès de la commune'])
            ->assertOk()->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseMissing('geo_units', ['name' => 'Bokoro']);
        $this->assertNotNull(GeoSuggestion::find($suggestion->id)->reviewed_at);
        $this->assertTrue(ActivityLog::where('action', 'review_geo_suggestion')->where('subject_id', $suggestion->id)->exists());
    }

    public function test_suggestions_are_reserved_to_administrators(): void
    {
        $suggestion = GeoSuggestion::create(['name' => 'Bokoro', 'status' => 'new']);

        $this->getJson('/api/v1/admin/geo/suggestions')->assertUnauthorized();
        $this->putJson("/api/v1/admin/geo/suggestions/{$suggestion->id}", ['status' => 'accepted'])->assertUnauthorized();
    }

    public function test_unit_changes_keep_previous_and_new_values_in_the_log(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $unit = GeoUnit::create(['level' => 'province', 'name' => 'Batha', 'slug' => 'batha', 'status' => 'to_verify', 'is_active' => true]);

        $this->putJson("/api/v1/admin/geo/units/{$unit->id}", [
            'name' => 'Batha', 'status' => 'official', 'source_url' => 'https://example.test/ordonnance', 'verified_on' => '2026-10-21', 'is_active' => true,
        ])->assertOk();

        $log = ActivityLog::where('action', 'update_geo_unit')->where('subject_id', $unit->id)->firstOrFail();
        $this->assertSame('to_verify', $log->old_values['status']);
        $this->assertSame('official', $log->new_values['status']);
    }
}
