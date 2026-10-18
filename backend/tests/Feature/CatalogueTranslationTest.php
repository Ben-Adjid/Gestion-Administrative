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

    public function test_seeded_catalogue_uses_the_specification_categories_and_valid_slugs(): void
    {
        foreach (['identite', 'etat-civil', 'etudes', 'legalisations', 'sante', 'travail', 'justice', 'voyage'] as $slug) {
            $this->assertDatabaseHas('categories', ['slug' => $slug, 'is_active' => true]);
        }

        $this->getJson('/api/v1/demarches?per_page=50')->assertOk()->assertJsonCount(11, 'data');
        $this->getJson('/api/v1/demarches?category=etat-civil')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/demarches/passeport-biometrique')->assertOk()->assertJsonPath('data.categorie.slug', 'voyage');
    }

    public function test_demarche_is_served_in_the_requested_language(): void
    {
        $this->getJson('/api/v1/demarches/acte-de-naissance?lang=ar')
            ->assertOk()
            ->assertJsonPath('data.language', 'ar')
            ->assertJsonPath('data.is_translated', true)
            ->assertJsonPath('data.title', 'شهادة الميلاد')
            ->assertJsonPath('data.categorie.name', 'الحالة المدنية');

        $this->getJson('/api/v1/demarches/acte-de-naissance?lang=en')
            ->assertOk()
            ->assertJsonPath('data.title', 'Birth Certificate')
            ->assertJsonPath('data.procedure_steps.0', 'Go to the town hall or civil registry office of the place of birth');

        $this->getJson('/api/v1/demarches/acte-de-naissance')
            ->assertOk()
            ->assertJsonPath('data.title', 'Acte de Naissance');
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
            ->assertJsonPath('data.0.title', 'جواز السفر البيومتري');

        $this->getJson('/api/v1/search/suggest?' . http_build_query(['q' => 'Passport', 'lang' => 'en']))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Biometric Passport');
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
