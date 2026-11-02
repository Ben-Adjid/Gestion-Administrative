<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Categorie;
use App\Models\Demarche;
use App\Models\Signalement;
use Database\Seeders\DemarcheSeeder;
use Illuminate\Support\Facades\DB;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_published_demarches(): void
    {
        $cat = Categorie::create([
            'name' => 'Identité',
            'slug' => 'identite',
            'is_active' => true,
        ]);

        Demarche::create([
            'categorie_id' => $cat->id,
            'title' => 'Carte Nationale',
            'slug' => 'carte-nationale',
            'description' => 'Description détaillée',
            'cost' => 'Gratuit',
            'institution' => 'ANATS',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/demarches');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'title', 'slug', 'institution']]]);
    }

    public function test_can_search_demarches(): void
    {
        $cat = Categorie::create(['name' => 'Transport', 'slug' => 'transport', 'is_active' => true]);
        Demarche::create([
            'categorie_id' => $cat->id,
            'title' => 'Permis de conduire',
            'slug' => 'permis-de-conduire',
            'description' => 'Test',
            'cost' => 'Payant',
            'institution' => 'Ministère des transports',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/demarches?q=Permis');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_suggestions_never_expose_unpublished_demarches(): void
    {
        $category = Categorie::create(['name' => 'Services', 'slug' => 'services', 'is_active' => true]);
        Demarche::create([
            'categorie_id' => $category->id,
            'title' => 'Fiche confidentielle',
            'slug' => 'fiche-confidentielle',
            'description' => 'Brouillon interne',
            'cost' => 'Gratuit',
            'institution' => 'Agence Secrete',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/search/suggest?q=Agence');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_signalement_submission_with_validation(): void
    {
        $payload = [
            'type' => 'cout_different',
            'message' => 'Le prix a été révisé à la hausse au guichet.',
            'author_email' => 'citoyen@example.td',
        ];

        $response = $this->postJson('/api/v1/signalements', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('data.type', 'cout_different');
                 
        $this->assertDatabaseHas('signalements', [
            'type' => 'cout_different',
            'author_email' => 'citoyen@example.td',
            'status' => 'pending',
        ]);
    }

    public function test_signalement_with_email_sends_an_acknowledgement(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\RateLimiter::clear('signalements:127.0.0.1');

        $this->postJson('/api/v1/signalements', ['type' => 'autre', 'message' => 'Sans email, pas d’accusé.'])->assertCreated();
        \Illuminate\Support\Facades\Mail::assertNothingQueued();

        $this->postJson('/api/v1/signalements', ['type' => 'autre', 'message' => 'Le guichet a changé d’adresse.', 'author_email' => 'citoyen@test.td'])->assertCreated();
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\SignalementReceivedMail::class, fn ($mail) => $mail->hasTo('citoyen@test.td'));

        $html = (new \App\Mail\SignalementReceivedMail(\App\Models\Signalement::latest('id')->first()))->render();
        $this->assertStringContainsString('Le guichet a changé d’adresse.', $html);
    }

    public function test_invalid_signalements_do_not_consume_the_hourly_limit(): void
    {
        \Illuminate\Support\Facades\RateLimiter::clear('signalements:127.0.0.1');
        $valid = ['type' => 'autre', 'message' => 'Message de test suffisamment long.'];

        // Deux erreurs de saisie ne doivent pas bloquer le citoyen.
        $this->postJson('/api/v1/signalements', ['type' => 'autre', 'message' => 'court'])->assertStatus(422);
        $this->postJson('/api/v1/signalements', ['type' => 'autre', 'message' => 'court'])->assertStatus(422);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/signalements', $valid)->assertStatus(201);
        }

        $this->postJson('/api/v1/signalements', $valid, ['Accept-Language' => 'fr'])
            ->assertStatus(429)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, '3 signalements')
                && str_contains($message, '60 minutes') && !str_contains($message, '(s)'));
        $this->postJson('/api/v1/signalements', $valid, ['Accept-Language' => 'ar'])
            ->assertStatus(429)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, '60 دقيقة'));
        $this->assertSame(3, \App\Models\Signalement::count());
    }

    public function test_browsing_does_not_consume_the_login_rate_limit(): void
    {
        // Sans préfixe, toutes les règles « throttle » partageaient un seul compteur par IP.
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/v1/categories')->assertOk();
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'inconnu@example.td', 'password' => 'x'])
            ->assertStatus(401);
        $this->postJson('/api/v1/admin/auth/login', ['email' => 'inconnu@example.td', 'password' => 'x'])
            ->assertStatus(422);
    }

    public function test_database_seeder_does_not_duplicate_demo_records(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $tables = ['admins', 'categories', 'demarches', 'signalements', 'activity_logs'];
        $initialCounts = collect($tables)->mapWithKeys(fn (string $table) => [
            $table => \Illuminate\Support\Facades\DB::table($table)->count(),
        ]);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        foreach ($tables as $table) {
            $this->assertSame(
                $initialCounts[$table],
                \Illuminate\Support\Facades\DB::table($table)->count(),
                "Seeder duplicated rows in {$table}"
            );
        }
    }

    public function test_legacy_demarche_seeder_does_not_publish_unverified_demo_data(): void
    {
        $this->seed(DemarcheSeeder::class);

        $this->assertSame(0, Demarche::count());
    }

    public function test_public_page_view_stores_only_a_hashed_session_identifier(): void
    {
        $visitorId = '9f4d5e7a-1b2c-4d6e-8f90-123456789abc';

        $response = $this->postJson('/api/v1/analytics/page-views', [
            'visitor_id' => $visitorId,
            'path' => '/demarches',
        ]);

        $response->assertStatus(202)->assertJsonPath('recorded', true);
        $this->assertDatabaseHas('page_views', [
            'path' => '/demarches',
            'visitor_hash' => hash_hmac('sha256', $visitorId, (string) config('app.key')),
        ]);
        $this->assertDatabaseMissing('page_views', ['visitor_hash' => $visitorId]);
    }
}
