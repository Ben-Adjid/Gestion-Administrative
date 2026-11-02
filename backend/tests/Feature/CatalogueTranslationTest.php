<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeded_catalogue_contains_all_documented_procedures_and_categories(): void
    {
        foreach (['identite', 'etat-civil', 'voyage', 'etudes', 'travail', 'sante', 'justice', 'legalisations', 'commerce', 'transport'] as $slug) {
            $this->assertDatabaseHas('categories', ['slug' => $slug, 'is_active' => true]);
        }
        $this->assertSame(10, \App\Models\Categorie::where('is_active', true)->count());

        $this->getJson('/api/v1/demarches?per_page=50')
            ->assertOk()
            ->assertJsonCount(50, 'data')
            ->assertJsonPath('meta.total', 80)
            ->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/v1/demarches?per_page=50&page=2')
            ->assertOk()
            ->assertJsonCount(30, 'data');
        $this->getJson('/api/v1/demarches?category=etat-civil&per_page=100')->assertOk()->assertJsonCount(11, 'data');
        $this->getJson('/api/v1/demarches/passeport-biometrique')->assertOk()->assertJsonPath('data.categorie.slug', 'voyage');

        $expectedCounts = [
            'identite' => 11,
            'etat-civil' => 11,
            'voyage' => 7,
            'etudes' => 9,
            'travail' => 8,
            'sante' => 7,
            'justice' => 6,
            'legalisations' => 7,
            'commerce' => 8,
            'transport' => 6,
        ];
        foreach ($expectedCounts as $categorySlug => $expectedCount) {
            $this->assertSame(
                $expectedCount,
                \App\Models\Demarche::whereHas('categorie', fn ($query) => $query->where('slug', $categorySlug))
                    ->where('status', 'published')
                    ->count(),
                "Nombre de démarches incorrect pour {$categorySlug}"
            );
        }

        foreach (\App\Models\Demarche::where('status', 'published')->get() as $demarche) {
            $this->assertNotEmpty($demarche->title);
            $this->assertNotEmpty($demarche->cost);
            $this->assertNotEmpty($demarche->processing_time);
            $this->assertNotEmpty($demarche->validity);
            $this->assertNotEmpty($demarche->required_documents);
            $this->assertNotEmpty($demarche->conditions);
            $this->assertNotEmpty($demarche->procedure_steps);
        }

        $cni = \App\Models\Demarche::where('slug', 'carte-didentite-nationale')->firstOrFail();
        $this->assertSame('2 à 8 semaines selon disponibilité', $cni->getAttribute('processing_time'));
        $this->assertSame('10 ans', $cni->getAttribute('validity'));
        $this->assertSame(6, \count($cni->getAttribute('required_documents')));
        $this->assertSame(6, \count($cni->getAttribute('procedure_steps')));
        $this->assertSame(6, \count($cni->getAttribute('conditions')));
        $this->assertSame("2 photos d'identité récentes (fond blanc, 4x4 cm)", $cni->getAttribute('required_documents')[1]);
    }

    public function test_demarche_is_served_in_the_requested_language(): void
    {
        $this->getJson('/api/v1/demarches/acte-de-naissance?lang=ar')
            ->assertOk()
            ->assertJsonPath('data.language', 'ar')
            ->assertJsonPath('data.is_translated', false)
            ->assertJsonPath('data.title', 'مستخرج شهادة الميلاد (نسخة)')
            ->assertJsonPath('data.categorie.name', 'الحالة المدنية');

        $this->getJson('/api/v1/demarches/acte-de-naissance?lang=en')
            ->assertOk()
            ->assertJsonPath('data.title', 'Birth Certificate Extract (Copy)')
            ->assertJsonPath('data.is_translated', false)
            ->assertJsonPath('data.procedure_steps.0', 'Se rendre à la mairie du lieu de naissance');

        $this->getJson('/api/v1/demarches/acte-de-naissance')
            ->assertOk()
            ->assertJsonPath('data.title', "Extrait d'acte de naissance (copie)");
    }

    public function test_validation_messages_follow_the_requested_language(): void
    {
        $this->postJson('/api/v1/auth/login', [], ['Accept-Language' => 'ar'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'حقل البريد الإلكتروني مطلوب.');

        $this->postJson('/api/v1/auth/login', [], ['Accept-Language' => 'fr'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'L’email est obligatoire.');

        $this->postJson('/api/v1/auth/login', [], ['Accept-Language' => 'en'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    public function test_search_and_suggestions_work_in_arabic(): void
    {
        $this->getJson('/api/v1/demarches?' . http_build_query(['q' => 'جواز', 'lang' => 'ar']))
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'passeport-biometrique')
            ->assertJsonPath('data.0.title', 'جواز السفر التشادي العادي — الطلب الأول');

        $this->getJson('/api/v1/search/suggest?' . http_build_query(['q' => 'Passport', 'lang' => 'en']))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Chadian Ordinary Passport — First Application');
    }

    public function test_catalogue_normalization_keeps_the_team_category_choices(): void
    {
        $category = \App\Models\Categorie::where('slug', 'identite')->first();
        $category->update(['name' => 'Identité et nationalité', 'display_order' => 9, 'color' => 'red']);

        $this->artisan('catalogue:normalize', ['--apply' => true])->assertSuccessful();

        $category->refresh();
        $this->assertSame(['Identité et nationalité', 9, 'red'], [$category->name, $category->display_order, $category->color]);
    }

    public function test_business_error_messages_follow_the_requested_language(): void
    {
        $credentials = ['email' => 'inconnu@example.td', 'password' => 'Mauvais-Mot2passe!'];

        $this->postJson('/api/v1/admin/auth/login', $credentials, ['Accept-Language' => 'ar'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'البريد الإلكتروني أو كلمة المرور غير صحيحة.');

        $this->postJson('/api/v1/admin/auth/login', $credentials, ['Accept-Language' => 'en'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Incorrect email or password.');

        $this->postJson('/api/v1/auth/login', $credentials, ['Accept-Language' => 'fr'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Email ou mot de passe incorrect.');
    }
}
