<?php

namespace Tests\Feature;

use App\Mail\QuestionAnsweredMail;
use App\Mail\VerificationReminderMail;
use App\Models\Admin;
use App\Models\CalendarEvent;
use App\Models\Categorie;
use App\Models\City;
use App\Models\Demarche;
use App\Models\LifeEvent;
use App\Models\Office;
use App\Models\Question;
use App\Models\SearchMiss;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class EngagementFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categorie = Categorie::create(['name' => 'Identité', 'slug' => 'identite', 'is_active' => true]);
    }

    private function admin(string $role = 'admin'): Admin
    {
        return Admin::create([
            'name' => ucfirst($role), 'email' => "{$role}@engagement.test",
            'password' => Hash::make('Secret123!'), 'role' => $role, 'is_active' => true,
        ]);
    }

    private function demarche(string $slug, array $attributes = []): Demarche
    {
        return Demarche::create($attributes + [
            'categorie_id' => $this->categorie->id, 'title' => ucfirst(str_replace('-', ' ', $slug)), 'slug' => $slug,
            'description' => 'Description.', 'cost' => '5 000 FCFA', 'cost_amount' => 5000,
            'institution' => 'Mairie', 'status' => 'published', 'required_documents' => ['Photo'],
        ]);
    }

    // ------------------------------------------------------------------ Coût total et bureaux

    public function test_fiche_exposes_cost_estimate_and_offices(): void
    {
        $demarche = $this->demarche('passeport', [
            'extra_costs' => [['label' => 'Photos', 'label_ar' => 'صور', 'amount' => 2000], ['label' => 'Timbre', 'amount' => 1000]],
        ]);
        $city = City::create(['name' => 'Moundou', 'slug' => 'moundou']);
        $office = Office::create(['city_id' => $city->id, 'name' => 'Commissariat central', 'address' => 'Avenue X', 'latitude' => 8.56, 'longitude' => 16.08]);
        Office::create(['city_id' => $city->id, 'name' => 'Bureau fermé', 'is_active' => false])->demarches()->attach($demarche);
        $office->demarches()->attach($demarche);

        $this->getJson('/api/v1/demarches/passeport')->assertOk()
            ->assertJsonPath('data.cost_estimate.total', 8000)
            ->assertJsonPath('data.cost_estimate.complete', true)
            ->assertJsonCount(1, 'data.offices')
            ->assertJsonPath('data.offices.0.city.slug', 'moundou')
            ->assertJsonPath('data.offices.0.directions_url', 'https://www.google.com/maps/dir/?api=1&destination=8.56%2C16.08');

        $this->getJson('/api/v1/demarches/passeport?lang=ar')->assertJsonPath('data.extra_costs.0.label', 'صور')
            ->assertJsonPath('data.extra_costs.1.label', 'Timbre');
        // La rubrique Diaspora a été retirée : plus aucune donnée « depuis l’étranger » n’est exposée.
        $this->getJson('/api/v1/demarches/passeport')->assertJsonMissingPath('data.abroad_info')->assertJsonMissingPath('data.abroad_available');
        $this->getJson('/api/v1/diaspora')->assertNotFound();
    }

    public function test_fiche_states_the_competent_authority_and_the_city_of_its_written_place(): void
    {
        $ndjamena = City::create(['name' => 'N’Djaména', 'slug' => 'ndjamena']);
        $moundou = City::create(['name' => 'Moundou', 'slug' => 'moundou']);
        $demarche = $this->demarche('acte-naissance', ['location' => 'Mairie de N’Djaména', 'authority_level' => 'local', 'location_city_id' => $ndjamena->id]);
        // Bureau sans adresse ni coordonnées : pas d'itinéraire (une recherche sur le seul nom pourrait mener ailleurs).
        Office::create(['city_id' => $moundou->id, 'name' => 'Centre d’état civil'])->demarches()->attach($demarche);
        Office::create(['city_id' => $moundou->id, 'name' => 'Mairie', 'address' => 'Avenue Y'])->demarches()->attach($demarche);

        $data = $this->getJson('/api/v1/demarches/acte-naissance')->assertOk()->json('data');
        $this->assertSame('local', $data['authority_level']);
        $this->assertSame(['slug' => 'ndjamena', 'name' => 'N’Djaména'], $data['location_city']);
        $byName = collect($data['offices'])->keyBy('name');
        $this->assertNull($byName['Centre d’état civil']['directions_url']);
        $this->assertStringContainsString('Avenue%20Y', $byName['Mairie']['directions_url']);
        $this->assertArrayNotHasKey('location_city_id', $data, 'Valeur brute réservée à l’administration.');

        // Lieu par ville : seul le texte rattaché à la ville choisie est « vérifié » ; un texte général ne l'est jamais.
        $this->getJson('/api/v1/demarches/acte-naissance?city=moundou')->assertJsonPath('place.has_verified_place', false)->assertJsonPath('place.written_place', null);
        // Lieu écrit sans source officielle : il est affiché, mais il n'est PAS un lieu vérifié.
        $this->getJson('/api/v1/demarches/acte-naissance?city=ndjamena')->assertJsonPath('place.has_verified_place', false)->assertJsonPath('place.written_place', 'Mairie de N’Djaména')->assertJsonPath('place.written_place_verified', false);

        $this->actingAs($this->admin('editor'), 'sanctum');
        $this->putJson("/api/v1/admin/demarches/{$demarche->id}", ['authority_level' => 'mondial'])->assertStatus(422)->assertJsonValidationErrors('authority_level');
        $this->putJson("/api/v1/admin/demarches/{$demarche->id}", ['location_city_id' => 99999])->assertStatus(422)->assertJsonValidationErrors('location_city_id');
        $this->putJson("/api/v1/admin/demarches/{$demarche->id}", ['authority_level' => 'regional', 'location_city_id' => null])->assertOk()
            ->assertJsonPath('data.authority_level', 'regional')->assertJsonPath('data.location_city', null);
    }

    public function test_price_range_makes_the_estimate_incomplete(): void
    {
        $this->demarche('consultation', ['cost' => '5 000 – 10 000 FCFA', 'cost_amount' => null, 'extra_costs' => [['label' => 'Carnet', 'amount' => 500]]]);

        $this->getJson('/api/v1/demarches/consultation')
            ->assertJsonPath('data.cost_estimate.complete', false)
            ->assertJsonPath('data.cost_estimate.total', 500);
    }

    // ------------------------------------------------------------------ Parcours et villes

    public function test_reference_seeder_builds_cities_and_complete_life_events_only_once(): void
    {
        foreach (['acte-de-naissance', 'carte-didentite-nationale', 'casier-judiciaire', 'passeport-biometrique'] as $slug) {
            $this->demarche($slug);
        }

        $this->seed(ReferenceDataSeeder::class);
        $this->seed(ReferenceDataSeeder::class);

        $this->assertSame(24, City::count());
        // Seuls les parcours dont toutes les fiches existent sont créés (voyager, papiers d'identité, bébé).
        $this->assertEqualsCanonicalizing(['voyager-a-l-etranger', 'obtenir-mes-papiers', 'j-ai-un-bebe'], LifeEvent::pluck('slug')->all());

        $this->getJson('/api/v1/life-events/voyager-a-l-etranger?lang=en')->assertOk()
            ->assertJsonPath('data.title', 'I want to travel abroad')
            ->assertJsonPath('data.steps.0.slug', 'acte-de-naissance')
            ->assertJsonPath('data.steps.3.slug', 'passeport-biometrique')
            ->assertJsonPath('data.total.amount', 20000)
            ->assertJsonPath('data.total.complete', true);

        $this->getJson('/api/v1/cities')->assertOk()->assertJsonCount(24, 'data');
        $this->getJson('/api/v1/cities/abeche?lang=ar')->assertOk()->assertJsonPath('data.name', 'أبشي');
    }

    // ------------------------------------------------------------------ Calendrier

    public function test_calendar_shows_only_published_current_events_and_exports_ics(): void
    {
        $visible = CalendarEvent::create(['title' => 'Révision des listes électorales', 'category' => 'elections', 'kind' => 'period', 'starts_on' => now()->subDay(), 'ends_on' => now()->addMonth(), 'is_published' => true]);
        CalendarEvent::create(['title' => 'Brouillon', 'category' => 'other', 'starts_on' => now()->addDay(), 'is_published' => false]);
        CalendarEvent::create(['title' => 'Passé', 'category' => 'other', 'starts_on' => now()->subMonth(), 'ends_on' => now()->subWeek(), 'is_published' => true]);

        $this->getJson('/api/v1/calendar')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $visible->id);
        $this->getJson('/api/v1/calendar?category=exams')->assertJsonCount(0, 'data');

        $ics = $this->get("/api/v1/calendar/{$visible->id}.ics")->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $this->assertStringContainsString('SUMMARY:Révision des listes électorales', $ics->getContent());
        $this->assertStringContainsString('DTSTART;VALUE=DATE:' . now()->subDay()->format('Ymd'), $ics->getContent());
    }

    public function test_calendar_distinguishes_periods_deadlines_and_single_events(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $base = ['title' => 'Échéance', 'category' => 'exams', 'is_published' => true];
        $in10 = now()->addDays(10)->toDateString();

        // Règles par type : une période exige une fin postérieure au début ; une date limite ou un événement n'en ont pas.
        $this->postJson('/api/v1/admin/calendar', $base + ['kind' => 'period', 'starts_on' => $in10])->assertStatus(422)->assertJsonValidationErrors('ends_on');
        $this->postJson('/api/v1/admin/calendar', $base + ['kind' => 'period', 'starts_on' => $in10, 'ends_on' => $in10])->assertStatus(422)->assertJsonValidationErrors('ends_on');
        $this->postJson('/api/v1/admin/calendar', $base + ['kind' => 'deadline', 'starts_on' => $in10, 'ends_on' => now()->addDays(20)->toDateString()])->assertStatus(422)->assertJsonValidationErrors('ends_on');
        $this->postJson('/api/v1/admin/calendar', $base + ['kind' => 'inconnu', 'starts_on' => $in10])->assertStatus(422)->assertJsonValidationErrors('kind');

        $deadline = $this->postJson('/api/v1/admin/calendar', ['title' => 'Dépôt des dossiers', 'kind' => 'deadline', 'starts_on' => $in10] + $base)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/admin/calendar', ['title' => 'Inscriptions', 'kind' => 'period', 'starts_on' => now()->subDays(2)->toDateString(), 'ends_on' => now()->addDays(5)->toDateString()] + $base)->assertCreated();
        $this->postJson('/api/v1/admin/calendar', ['title' => 'Épreuve écrite', 'kind' => 'event', 'starts_on' => now()->addDays(30)->toDateString()] + $base)->assertCreated();
        $this->app['auth']->forgetGuards();

        $items = collect($this->getJson('/api/v1/calendar')->assertOk()->json('data'))->keyBy('title');
        $this->assertSame(['deadline', 'upcoming', 10], [$items['Dépôt des dossiers']['kind'], $items['Dépôt des dossiers']['status'], $items['Dépôt des dossiers']['days_left']]);
        // Période en cours : jours restants jusqu'à la fin.
        $this->assertSame(['period', 'ongoing', 5], [$items['Inscriptions']['kind'], $items['Inscriptions']['status'], $items['Inscriptions']['days_left']]);
        $this->assertSame(['event', 30], [$items['Épreuve écrite']['kind'], $items['Épreuve écrite']['days_left']]);

        $this->getJson('/api/v1/calendar?kind=deadline')->assertJsonCount(1, 'data');

        $ics = $this->get("/api/v1/calendar/{$deadline}.ics?lang=fr")->getContent();
        $this->assertStringContainsString('SUMMARY:Date limite : Dépôt des dossiers', $ics);
        $this->assertStringContainsString('TRIGGER:-P3D', $ics);
    }

    // ------------------------------------------------------------------ Avis des citoyens

    public function test_review_is_published_only_after_moderation_with_median_price(): void
    {
        $this->demarche('acte');
        RateLimiter::clear('public-submission:review:127.0.0.1');
        $review = ['visit_date' => now()->subDays(3)->toDateString(), 'rating' => 4, 'comment' => 'Accueil correct, un peu d’attente.'];

        $this->postJson('/api/v1/demarches/acte/reviews', ['rating' => 9, 'comment' => 'court'])->assertStatus(422);
        $this->postJson('/api/v1/demarches/acte/reviews', $review + ['price_paid' => 1000])->assertCreated();
        $this->postJson('/api/v1/demarches/acte/reviews', $review + ['price_paid' => 3000])->assertCreated();
        $this->postJson('/api/v1/demarches/acte/reviews', $review + ['price_paid' => 2000])->assertCreated();

        $this->getJson('/api/v1/demarches/acte/reviews')->assertJsonPath('summary.count', 0);

        $this->actingAs($this->admin(), 'sanctum');
        foreach ($this->getJson('/api/v1/admin/reviews?status=pending')->json('data') as $item) {
            $this->patchJson("/api/v1/admin/reviews/{$item['id']}", ['status' => 'approved'])->assertOk();
        }

        $this->getJson('/api/v1/demarches/acte/reviews')
            ->assertJsonPath('summary.count', 3)
            ->assertJsonPath('summary.median_price', 2000)
            ->assertJsonPath('summary.average_rating', 4);
    }

    public function test_bot_honeypot_and_hourly_limit_protect_public_forms(): void
    {
        $this->demarche('acte');
        RateLimiter::clear('public-submission:question:127.0.0.1');
        $question = ['body' => 'Peut-on faire la démarche pour un parent ?'];

        $this->postJson('/api/v1/questions', $question + ['website' => 'http://spam.example'])->assertCreated();
        $this->assertSame(0, Question::count());

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/questions', $question)->assertCreated();
        }
        $this->postJson('/api/v1/questions', $question)->assertStatus(429);
        $this->assertSame(5, Question::count());
    }

    // ------------------------------------------------------------------ Questions-réponses

    public function test_question_answer_flow_with_moderation_and_email(): void
    {
        Mail::fake();
        $this->demarche('acte');
        RateLimiter::clear('public-submission:question:127.0.0.1');
        RateLimiter::clear('public-submission:answer:127.0.0.1');

        $this->postJson('/api/v1/questions', [
            'body' => 'Faut-il être présent en personne ?', 'author_email' => 'citoyen@test.td', 'demarche_slug' => 'acte',
        ])->assertCreated();
        $this->getJson('/api/v1/questions')->assertJsonCount(0, 'data');

        $this->actingAs($this->admin(), 'sanctum');
        $id = Question::first()->id;
        $this->postJson("/api/v1/admin/questions/{$id}/answers", ['body' => 'Oui, la présence est obligatoire.'])->assertCreated();
        Mail::assertQueued(QuestionAnsweredMail::class, fn ($mail) => $mail->hasTo('citoyen@test.td'));

        $this->app['auth']->forgetGuards();
        $this->postJson("/api/v1/questions/{$id}/answers", ['body' => 'Je confirme, j’y suis allé la semaine dernière.'])->assertCreated();

        $this->getJson('/api/v1/questions?demarche=acte')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(1, 'data.0.answers')
            ->assertJsonPath('data.0.answers.0.is_official', true);

        $this->actingAs(Admin::first(), 'sanctum');
        $pending = $this->getJson('/api/v1/admin/questions?status=pending_answers')->json('data.0.answers');
        $community = collect($pending)->firstWhere('is_official', false);
        $this->patchJson("/api/v1/admin/answers/{$community['id']}", ['status' => 'published'])->assertOk();

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/questions')->assertJsonCount(2, 'data.0.answers');
    }

    // ------------------------------------------------------------------ Outils d'administration

    public function test_zero_result_searches_are_recorded_and_can_be_dismissed(): void
    {
        $this->demarche('passeport');

        $this->getJson('/api/v1/demarches?q=permis%20de%20chasse')->assertOk();
        $this->getJson('/api/v1/demarches?q=Permis  de chasse')->assertOk();
        $this->getJson('/api/v1/demarches?q=pa')->assertOk();
        $this->getJson('/api/v1/demarches?q=passeport')->assertOk();

        $this->assertSame(1, SearchMiss::count());
        $this->assertSame(2, SearchMiss::first()->count);

        $this->actingAs($this->admin('editor'), 'sanctum');
        $this->getJson('/api/v1/admin/search-misses')->assertOk()->assertJsonPath('data.0.term', 'permis de chasse');
        $this->patchJson('/api/v1/admin/search-misses/' . SearchMiss::first()->id)->assertForbidden();

        $this->actingAs($this->admin('admin'), 'sanctum');
        $this->patchJson('/api/v1/admin/search-misses/' . SearchMiss::first()->id)->assertOk();
        $this->getJson('/api/v1/admin/search-misses')->assertJsonCount(0, 'data');
    }

    public function test_verify_marks_fiche_and_reminder_targets_subscribed_moderators(): void
    {
        Mail::fake();
        $demarche = $this->demarche('acte');
        $this->admin('admin');
        $this->admin('editor');

        $this->artisan('demarches:verification-reminder')->assertSuccessful();
        Mail::assertQueued(VerificationReminderMail::class, 1);
        Mail::assertQueued(VerificationReminderMail::class, fn ($mail) => $mail->hasTo('admin@engagement.test'));

        $this->actingAs(Admin::where('role', 'editor')->first(), 'sanctum');
        $this->patchJson("/api/v1/admin/demarches/{$demarche->id}/verify")->assertForbidden();

        $this->actingAs(Admin::where('role', 'admin')->first(), 'sanctum');
        $this->patchJson("/api/v1/admin/demarches/{$demarche->id}/verify")->assertOk();
        $this->assertTrue($demarche->fresh()->last_verified_at->isToday());
    }

    public function test_editor_cannot_manage_reference_data_but_can_read_offices(): void
    {
        $city = City::create(['name' => 'Sarh', 'slug' => 'sarh']);
        $this->actingAs($this->admin('editor'), 'sanctum');

        $this->getJson('/api/v1/admin/offices')->assertOk();
        $this->postJson('/api/v1/admin/offices', ['city_id' => $city->id, 'name' => 'X'])->assertForbidden();
        $this->postJson('/api/v1/admin/calendar', ['title' => 'X', 'category' => 'other', 'kind' => 'event', 'starts_on' => '2026-11-01'])->assertForbidden();
        $this->postJson('/api/v1/admin/life-events', ['title' => 'X', 'demarche_ids' => [1]])->assertForbidden();
        $this->getJson('/api/v1/admin/reviews')->assertForbidden();
        $this->getJson('/api/v1/admin/questions')->assertForbidden();
        $this->postJson('/api/v1/admin/import', [])->assertForbidden();
    }

    public function test_moderator_manages_offices_life_events_and_calendar(): void
    {
        $acte = $this->demarche('acte');
        $cni = $this->demarche('cni');
        $this->actingAs($this->admin(), 'sanctum');

        $cityId = $this->postJson('/api/v1/admin/cities', ['name' => 'Bongor'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/admin/offices', [
            'city_id' => $cityId, 'name' => 'Mairie de Bongor', 'latitude' => 10.28, 'demarche_ids' => [$acte->id],
        ])->assertStatus(422)->assertJsonValidationErrors('longitude');
        $this->postJson('/api/v1/admin/offices', ['city_id' => $cityId, 'name' => 'Mairie de Bongor', 'demarche_ids' => [$acte->id]])->assertCreated();
        $this->deleteJson("/api/v1/admin/cities/{$cityId}")->assertStatus(409);

        $this->postJson('/api/v1/admin/life-events', ['title' => 'Test parcours', 'demarche_ids' => [$cni->id, $acte->id]])->assertCreated();
        $this->getJson('/api/v1/life-events/test-parcours')->assertJsonPath('data.steps.0.slug', 'cni')->assertJsonPath('data.steps.1.slug', 'acte');

        $this->postJson('/api/v1/admin/calendar', ['title' => 'Rentrée', 'category' => 'education', 'kind' => 'period', 'starts_on' => '2026-11-02', 'ends_on' => '2026-10-01'])
            ->assertStatus(422)->assertJsonValidationErrors('ends_on');
        $this->postJson('/api/v1/admin/calendar', ['title' => 'Rentrée', 'category' => 'education', 'kind' => 'event', 'starts_on' => now()->addWeek()->toDateString(), 'is_published' => true])->assertCreated();
        $this->assertSame('create_calendar_event', \App\Models\ActivityLog::latest('id')->value('action'));
    }

    public function test_demarche_form_saves_extra_costs_and_offices(): void
    {
        $demarche = $this->demarche('acte');
        $office = Office::create(['city_id' => City::create(['name' => 'Mao', 'slug' => 'mao'])->id, 'name' => 'Mairie de Mao']);
        $this->actingAs($this->admin('editor'), 'sanctum');

        $this->putJson("/api/v1/admin/demarches/{$demarche->id}", [
            'extra_costs' => [['label' => 'Photos', 'amount' => 2000]],
            'office_ids' => [$office->id],
        ])->assertOk()
            ->assertJsonPath('data.cost_estimate.total', 7000)
            ->assertJsonPath('data.office_ids', [$office->id]);

        $this->putJson("/api/v1/admin/demarches/{$demarche->id}", ['extra_costs' => [['label' => 'Photos', 'amount' => -5]]])
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------ Import Excel / CSV

    public function test_import_checks_every_line_then_creates_drafts_only(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $this->demarche('existante', ['title' => 'Démarche existante']);

        $csv = "titre;categorie;institution;cout;description;pieces\n"
            . "Légalisation de signature;identite;Mairie;500 FCFA;Certifier une signature.;\"Pièce d'identité ; Document\"\n"
            . "Démarche existante;identite;Mairie;Gratuit;Doublon.;\n"
            . "Sans catégorie;inconnue;Mairie;Gratuit;Test.;\n";
        $file = fn () => UploadedFile::fake()->createWithContent('import.csv', $csv);

        $report = $this->post('/api/v1/admin/import', ['file' => $file(), 'dry_run' => 1], ['Accept' => 'application/json'])->assertOk()->json('data');
        $this->assertSame(1, $report['valid']);
        $this->assertSame([3, 4], array_column($report['errors'], 'line'));

        // Avec des erreurs, l'import réel n'enregistre rien.
        $this->post('/api/v1/admin/import', ['file' => $file()], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.created', 0);
        $this->assertSame(1, Demarche::count());

        $clean = "titre;categorie;institution;cout;description;pieces\nLégalisation de signature;Identité;Mairie;500 FCFA;Certifier.;\"Pièce d'identité ; Document\"\n";
        $this->post('/api/v1/admin/import', ['file' => UploadedFile::fake()->createWithContent('ok.csv', $clean)], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.created', 1);

        $created = Demarche::where('title', 'Légalisation de signature')->first();
        $this->assertSame('draft', $created->status);
        $this->assertSame(500, (int) $created->cost_amount);
        $this->assertSame(["Pièce d'identité", 'Document'], $created->required_documents);
    }

    public function test_excel_template_can_be_downloaded_and_reimported(): void
    {
        Categorie::create(['name' => 'Légalisations', 'slug' => 'legalisations', 'is_active' => true]);
        $this->actingAs($this->admin(), 'sanctum');

        $response = $this->get('/api/v1/admin/import/template')->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $copy = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        copy($path, $copy);

        $upload = new UploadedFile($copy, 'modele.xlsx', null, null, true);
        $this->post('/api/v1/admin/import', ['file' => $upload, 'dry_run' => 1], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.valid', 1)->assertJsonPath('data.errors', []);

        $this->post('/api/v1/admin/import', ['file' => UploadedFile::fake()->create('virus.exe', 10)], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_document_validity_rules_never_invent_a_duration(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $rules = collect($this->getJson('/api/v1/document-validity?lang=ar')->assertOk()->json('data'))->keyBy('key');

        // Sourcée (source officielle secondaire) : durée, point de départ, source et date de vérification.
        $cni = $rules['carte-nationale-identite'];
        $this->assertSame(120, $cni['validity_months']);
        $this->assertSame('secondary', $cni['status']);
        $this->assertTrue($cni['can_compute_expiry']);
        $this->assertNotEmpty($cni['source_url']);
        $this->assertSame('2026-10-16', $cni['verified_on']);
        $this->assertSame('بطاقة التعريف الوطنية', $cni['name']);

        // Non sourcées : aucune durée, aucune date d'expiration calculable.
        foreach (['passeport-ordinaire', 'casier-judiciaire'] as $key) {
            $this->assertNull($rules[$key]['validity_months'], $key);
            $this->assertFalse($rules[$key]['can_compute_expiry'], $key);
            $this->assertSame('to_verify', $rules[$key]['status'], $key);
        }

        // Actes d'état civil : pas de date d'expiration en soi (validité permanente, ou liée au statut pour le mariage).
        foreach (['acte-de-naissance' => 'permanent', 'acte-de-deces' => 'permanent', 'acte-de-mariage' => 'status_based'] as $key => $status) {
            $this->assertNull($rules[$key]['validity_months'], $key);
            $this->assertFalse($rules[$key]['can_compute_expiry'], $key);
            $this->assertSame($status, $rules[$key]['status'], $key);
            $this->assertSame('not_applicable', $rules[$key]['validity_start'], $key);
        }

        // Calcul : 10 ans après la délivrance (29/02 → 28/02 d’une année non bissextile) ; jamais pour une règle « à vérifier ».
        $rule = \App\Models\DocumentValidityRule::where('key', 'carte-nationale-identite')->first();
        $this->assertSame('2034-02-28', $rule->expiryFor(\Illuminate\Support\Carbon::parse('2024-02-29'))->toDateString());
        $this->assertNull(\App\Models\DocumentValidityRule::where('key', 'acte-de-naissance')->first()->expiryFor(now()));

        // Administration : réservée aux modérateurs ; pas de durée sans source, pas de durée « à vérifier ».
        $body = ['key' => 'carte-grise', 'name' => 'Carte grise', 'category' => 'transport', 'validity_start' => 'issue_date'];
        \Laravel\Sanctum\Sanctum::actingAs($this->admin('editor'));
        $this->postJson('/api/v1/admin/document-validity', $body + ['status' => 'to_verify'])->assertForbidden();
        \Laravel\Sanctum\Sanctum::actingAs($this->admin('admin'));
        $this->postJson('/api/v1/admin/document-validity', $body + ['status' => 'to_verify', 'validity_months' => 12])
            ->assertStatus(422)->assertJsonValidationErrors('validity_months');
        $this->postJson('/api/v1/admin/document-validity', $body + ['status' => 'confirmed', 'validity_months' => 12])
            ->assertStatus(422)->assertJsonValidationErrors(['source_url', 'source_label']);
        $this->postJson('/api/v1/admin/document-validity', $body + ['status' => 'to_verify'])->assertCreated();
        $this->postJson('/api/v1/admin/document-validity', $body + ['status' => 'to_verify'])->assertStatus(422)->assertJsonValidationErrors('key');
    }

    public function test_diaspora_section_is_removed_from_the_api(): void
    {
        $this->getJson('/api/v1/diaspora')->assertNotFound();
        \Laravel\Sanctum\Sanctum::actingAs($this->admin('super_admin'));
        $this->getJson('/api/v1/admin/representations')->assertNotFound();
        $this->postJson('/api/v1/admin/representations', ['country' => 'X'])->assertNotFound();
    }

    public function test_analytics_report_top_searches_monthly_signalements_and_conversion(): void
    {
        $this->demarche('casier-judiciaire', ['title' => 'Casier judiciaire']);

        // Recherches réelles : terme normalisé, compté, seule la première page compte, frappes courtes ignorées.
        $this->getJson('/api/v1/demarches?q=Casier')->assertOk();
        $this->getJson('/api/v1/demarches?q=%20%20casier%20')->assertOk();
        $this->getJson('/api/v1/demarches?q=casier&page=2')->assertOk();
        $this->getJson('/api/v1/demarches?q=visa%20chine')->assertOk();
        $this->getJson('/api/v1/demarches?q=ca')->assertOk();

        $this->assertSame(2, \App\Models\SearchTerm::where('term', 'casier')->value('count'));
        $this->assertSame(1, \App\Models\SearchTerm::where('term', 'casier')->value('last_results'));
        $this->assertSame(0, \App\Models\SearchTerm::where('term', 'visa chine')->value('last_results'));
        $this->assertFalse(\App\Models\SearchTerm::where('term', 'ca')->exists());
        $this->assertSame(1, SearchMiss::where('term', 'visa chine')->value('count'));

        // Une recherche sans résultat écartée revient si on la cherche encore.
        SearchMiss::where('term', 'visa chine')->update(['dismissed_at' => now()]);
        $this->getJson('/api/v1/demarches?q=visa%20chine')->assertOk();
        $this->assertNull(SearchMiss::where('term', 'visa chine')->value('dismissed_at'));
        $this->assertSame(2, SearchMiss::where('term', 'visa chine')->value('count'));

        // Signalements : un reçu il y a 2 mois et traité ce mois-ci, un reçu ce mois-ci.
        $old = \App\Models\Signalement::create(['type' => 'autre', 'message' => 'Message ancien de test.', 'status' => 'resolved', 'ip_address' => 'test']);
        $old->forceFill(['created_at' => now()->subMonthsNoOverflow(2), 'resolved_at' => now()])->save();
        \App\Models\Signalement::create(['type' => 'autre', 'message' => 'Message récent de test.', 'status' => 'pending', 'ip_address' => 'test']);
        foreach (['11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222'] as $visitor) {
            $this->postJson('/api/v1/analytics/page-views', ['visitor_id' => $visitor, 'path' => '/'])->assertAccepted();
        }

        $editor = $this->admin('editor');
        \Laravel\Sanctum\Sanctum::actingAs($editor);
        $this->getJson('/api/v1/admin/analytics')->assertForbidden();

        \Laravel\Sanctum\Sanctum::actingAs($this->admin('admin'));
        $data = $this->getJson('/api/v1/admin/analytics')->assertOk()->json();

        $this->assertSame('casier', $data['top_searches'][0]['term']);
        $this->assertCount(12, $data['monthly_signalements']);
        $months = collect($data['monthly_signalements'])->keyBy('month');
        $this->assertSame(1, $months[now()->format('Y-m')]['received']);
        $this->assertSame(1, $months[now()->format('Y-m')]['handled']);
        $this->assertSame(1, $months[now()->subMonthsNoOverflow(2)->format('Y-m')]['received']);
        $this->assertEquals(['days' => 30, 'visitors' => 2, 'signalements' => 1, 'rate' => 50], $data['conversion']);
    }
}
