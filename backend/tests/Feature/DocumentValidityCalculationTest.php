<?php

namespace Tests\Feature;

use App\Models\DocumentValidityRule;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\DocumentValidityCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Calcul de validité : règles sourcées, dates impossibles, années bissextiles, confidentialité. */
class DocumentValidityCalculationTest extends TestCase
{
    use RefreshDatabase;

    private DocumentValidityCalculator $calc;
    private Carbon $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new DocumentValidityCalculator();
        $this->today = Carbon::parse('2026-10-18');
    }

    private function rule(array $attributes = []): DocumentValidityRule
    {
        return DocumentValidityRule::create($attributes + [
            'key' => 'test-regle-' . uniqid(), 'name' => 'Règle test', 'category' => 'identity', 'country_code' => 'TD',
            'validity_months' => 120, 'validity_start' => 'issue_date', 'status' => 'secondary',
            'source_label' => 'Source test', 'source_url' => 'https://example.test/source', 'position' => 0, 'is_active' => true,
        ]);
    }

    public function test_leap_day_issue_gives_the_last_day_of_february_in_a_non_leap_year(): void
    {
        $result = $this->calc->evaluate($this->rule(), '2024-02-29', $this->today);
        $this->assertSame('2034-02-28', $result['expiry_date']);
        $this->assertSame('valid', $result['state']);
    }

    public function test_regular_dates_are_unchanged(): void
    {
        $this->assertSame('2030-05-10', $this->calc->evaluate($this->rule(), '2020-05-10', $this->today)['expiry_date']);
    }

    public function test_states_follow_remaining_days(): void
    {
        $expiring = $this->calc->evaluate($this->rule(['validity_months' => 12]), '2025-11-01', $this->today);
        $this->assertSame('expiring', $expiring['state']);

        $expired = $this->calc->evaluate($this->rule(['validity_months' => 12]), '2024-01-01', $this->today);
        $this->assertSame('expired', $expired['state']);
        $this->assertLessThan(0, $expired['days_left']);
    }

    public function test_impossible_or_out_of_range_issue_dates_are_refused(): void
    {
        $rule = $this->rule();
        foreach (['2023-02-30', '2026-13-01', '2099-01-01', '1800-01-01', 'pas-une-date', '', null] as $bad) {
            $this->assertSame('unavailable', $this->calc->evaluate($rule, $bad, $this->today)['state'], var_export($bad, true));
        }
    }

    public function test_rules_without_a_source_never_compute_an_expiry(): void
    {
        $unknown = $this->rule(['key' => 'inconnu', 'validity_months' => null, 'status' => 'to_verify', 'source_url' => null]);
        $result = $this->calc->evaluate($unknown, '2024-01-01', $this->today);
        $this->assertSame('unavailable', $result['state']);
        $this->assertNull($result['expiry_date']);
        $this->assertFalse($result['can_compute']);
    }

    public function test_citizen_creates_a_rule_based_document_with_computed_expiry(): void
    {
        $rule = $this->rule(['key' => 'cni-test', 'validity_months' => 120]);
        $user = User::create(['name' => 'Citoyen', 'email' => 'calc@test.td', 'password' => Hash::make('Secret123!')]);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/me/documents', [
            'type' => 'cni', 'validity_rule_id' => $rule->id, 'issue_date' => '2024-02-29',
            // L'expiration saisie est ignorée : le serveur applique la règle officielle.
            'expiration_date' => '2099-01-01',
        ])->assertCreated();

        $this->assertSame('2034-02-28', $response->json('data.expiration_date'));
    }

    public function test_permanent_and_status_based_rules_never_compute_an_expiry(): void
    {
        $permanent = $this->rule(['key' => 'naissance-test', 'validity_months' => null, 'validity_start' => 'not_applicable', 'status' => 'permanent']);
        $result = $this->calc->evaluate($permanent, '2001-05-10', $this->today);
        $this->assertSame(DocumentValidityCalculator::STATE_PERMANENT, $result['state']);
        $this->assertNull($result['expiry_date']);
        $this->assertNull($result['days_left']);
        $this->assertFalse($result['can_compute']);

        $marriage = $this->rule(['key' => 'mariage-test', 'validity_months' => null, 'validity_start' => 'not_applicable', 'status' => 'status_based']);
        $result = $this->calc->evaluate($marriage, '2015-05-10', $this->today);
        $this->assertSame(DocumentValidityCalculator::STATE_STATUS_BASED, $result['state']);
        $this->assertNull($result['expiry_date']);
        $this->assertStringContainsString('divorce', $result['message']);
    }

    public function test_criminal_record_is_valid_three_calendar_months(): void
    {
        $rule = $this->rule(['key' => 'casier-judiciaire', 'validity_months' => 3, 'status' => 'business_requirement', 'validity_start' => 'issue_date']);

        // Délivré le 31 août : valable jusqu'au 30 novembre (trois mois calendaires, pas 90 jours).
        $this->assertSame('2026-11-30', $this->calc->evaluate($rule, '2026-08-31', Carbon::parse('2026-10-01'))['expiry_date']);

        $expired = $this->calc->evaluate($rule, '2026-08-01', Carbon::parse('2026-11-06'));
        $this->assertSame(DocumentValidityCalculator::STATE_EXPIRED, $expired['state']);
        $this->assertStringContainsString('renouvellement à vérifier', $expired['message']);
    }

    public function test_cross_border_pass_is_valid_one_year(): void
    {
        $rule = $this->rule(['key' => 'test-laissez-passer', 'validity_months' => 12, 'status' => 'business_requirement', 'validity_start' => 'issue_date']);

        $this->assertSame('2027-03-01', $this->calc->evaluate($rule, '2026-03-01', Carbon::parse('2026-11-06'))['expiry_date']);
    }

    public function test_marriage_certificate_is_no_longer_valid_after_a_divorce(): void
    {
        $rule = $this->rule(['key' => 'acte-de-mariage', 'validity_months' => null, 'status' => 'status_based', 'validity_start' => 'not_applicable']);

        $result = $this->calc->evaluate($rule, '2015-06-01', Carbon::parse('2026-11-06'));
        $this->assertNull($result['expiry_date']);
        $this->assertStringContainsString('divorce', $result['message']);
        $this->assertStringContainsString('plus valable', $result['message']);
    }

    public function test_seed_data_gives_civil_status_acts_their_real_validity(): void
    {
        $rules = collect(require database_path('data/document_validity.php'))->keyBy('key');

        // Naissance et décès : validité permanente. Mariage : liée au statut matrimonial. Aucune durée inventée.
        foreach (['acte-de-naissance', 'acte-de-deces'] as $key) {
            $this->assertSame('permanent', $rules[$key]['status'], $key);
            $this->assertNull($rules[$key]['validity_months'], $key);
        }
        $this->assertSame('status_based', $rules['acte-de-mariage']['status']);
        $this->assertNull($rules['acte-de-mariage']['validity_months']);
    }

    public function test_civil_status_migration_updates_only_the_three_acts(): void
    {
        foreach (['acte-de-naissance', 'acte-de-deces', 'acte-de-mariage', 'casier-judiciaire'] as $key) {
            $this->rule(['key' => $key, 'validity_months' => null, 'validity_start' => 'unknown', 'status' => 'to_verify']);
        }

        (require database_path('migrations/2026_10_19_000001_set_civil_status_validity_rules.php'))->up();

        $this->assertSame('permanent', DocumentValidityRule::where('key', 'acte-de-naissance')->value('status'));
        $this->assertSame('permanent', DocumentValidityRule::where('key', 'acte-de-deces')->value('status'));
        $this->assertSame('status_based', DocumentValidityRule::where('key', 'acte-de-mariage')->value('status'));
        // Le casier n'est pas modifié par cette migration.
        $this->assertSame('to_verify', DocumentValidityRule::where('key', 'casier-judiciaire')->value('status'));
    }

    public function test_public_rule_list_exposes_the_id_needed_to_attach_a_document(): void
    {
        $rule = $this->rule(['key' => 'liste-id', 'validity_months' => null, 'validity_start' => 'not_applicable', 'status' => 'permanent']);

        $data = collect($this->getJson('/api/v1/document-validity?lang=fr')->assertOk()->json('data'))->keyBy('key');
        $this->assertSame($rule->id, $data['liste-id']['id']);
    }

    public function test_citizen_saves_a_birth_certificate_without_inventing_an_expiry(): void
    {
        $rule = $this->rule(['key' => 'naissance-doc', 'validity_months' => null, 'validity_start' => 'not_applicable', 'status' => 'permanent']);
        $user = User::create(['name' => 'Citoyen', 'email' => 'naissance@test.td', 'password' => Hash::make('Secret123!')]);
        $this->actingAs($user, 'sanctum');

        // Aucune date d'expiration saisie : le document est accepté, sans échéance.
        $response = $this->postJson('/api/v1/me/documents', [
            'type' => 'autre', 'validity_rule_id' => $rule->id, 'expiration_date' => '2099-01-01',
        ])->assertCreated();

        $this->assertNull($response->json('data.expiration_date'));
        $this->assertNull($response->json('data.days_remaining'));
        $this->assertSame('no_expiry', $response->json('data.state'));
        $this->assertSame('permanent', $response->json('data.validity.state'));
        $this->assertDatabaseHas('user_documents', ['user_id' => $user->id, 'expiration_date' => null]);
    }

    public function test_marriage_document_has_no_automatic_expiry_and_no_reminder(): void
    {
        $rule = $this->rule(['key' => 'mariage-doc', 'validity_months' => null, 'validity_start' => 'not_applicable', 'status' => 'status_based']);
        $user = User::create(['name' => 'Marié', 'email' => 'mariage@test.td', 'password' => Hash::make('Secret123!')]);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/me/documents', ['type' => 'autre', 'validity_rule_id' => $rule->id, 'issue_date' => '2015-06-01'])->assertCreated();
        $this->assertNull($response->json('data.expiration_date'));
        $this->assertSame('status_based', $response->json('data.validity.state'));

        $document = UserDocument::findOrFail($response->json('data.id'));
        $this->assertNull($document->daysUntilExpiration());
        $this->assertNull($document->dueReminderThreshold());
        $this->assertSame(0, UserDocument::expiringWithin(3650)->count());
    }

    public function test_a_document_without_rule_still_requires_an_expiry_date(): void
    {
        $user = User::create(['name' => 'Sans règle', 'email' => 'sansregle@test.td', 'password' => Hash::make('Secret123!')]);
        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/v1/me/documents', ['type' => 'autre'])->assertUnprocessable();
    }

    public function test_citizen_cannot_see_or_modify_another_citizens_document(): void
    {
        $owner = User::create(['name' => 'A', 'email' => 'a@test.td', 'password' => Hash::make('Secret123!')]);
        $other = User::create(['name' => 'B', 'email' => 'b@test.td', 'password' => Hash::make('Secret123!')]);
        $doc = $owner->documents()->create(['type' => 'cni', 'expiration_date' => now()->addYear()->toDateString()]);

        $this->actingAs($other, 'sanctum');
        $this->getJson('/api/v1/me/documents')->assertOk()->assertJsonCount(0, 'data');
        $this->putJson("/api/v1/me/documents/{$doc->id}", ['type' => 'cni', 'expiration_date' => now()->addYears(5)->toDateString()])->assertNotFound();
        $this->deleteJson("/api/v1/me/documents/{$doc->id}")->assertNotFound();
    }
}
