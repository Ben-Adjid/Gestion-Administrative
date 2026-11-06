<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Categorie;
use App\Models\Demarche;

class ImportDemarcheTranslationsTest extends TestCase
{
    use RefreshDatabase;

    private function fiche(string $slug): Demarche
    {
        $cat = Categorie::create(['name' => 'Civil ' . $slug, 'slug' => 'civil-' . $slug, 'is_active' => true]);

        return Demarche::create([
            'categorie_id' => $cat->id,
            'title' => 'Titre ' . $slug,
            'slug' => $slug,
            'description' => 'Description en français.',
            'cost' => '500 FCFA',
            'institution' => 'Mairie',
            'status' => 'published',
        ]);
    }

    private function writeJson(array $data): string
    {
        $path = sys_get_temp_dir() . '/trad-' . uniqid() . '.json';
        file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE));

        return $path;
    }

    public function test_imports_translations_and_keeps_existing_ones_by_default(): void
    {
        $d = $this->fiche('fiche-test');
        $d->update(['cost_ar' => 'قيمة موجودة']);

        $path = $this->writeJson([
            ['slug' => 'fiche-test', 'en' => ['cost' => '500 FCFA', 'description' => 'English description.', 'procedure_steps' => ['Step one']],
             'ar' => ['cost' => 'ألف', 'description' => 'وصف بالعربية.']],
            ['slug' => 'slug-inconnu', 'en' => ['cost' => 'x']],
        ]);

        $this->artisan('demarches:import-translations', ['file' => $path])->assertSuccessful();

        $d->refresh();
        $this->assertSame('English description.', $d->description_en);
        $this->assertSame(['Step one'], $d->procedure_steps_en);
        $this->assertSame('وصف بالعربية.', $d->description_ar);
        $this->assertSame('قيمة موجودة', $d->cost_ar, 'Une traduction déjà renseignée est conservée.');
    }

    public function test_overwrite_option_replaces_existing_translations(): void
    {
        $d = $this->fiche('fiche-ecrasee');
        $d->update(['cost_ar' => 'قديم']);

        $path = $this->writeJson([['slug' => 'fiche-ecrasee', 'ar' => ['cost' => 'جديد']]]);

        $this->artisan('demarches:import-translations', ['file' => $path, '--overwrite' => true])->assertSuccessful();

        $this->assertSame('جديد', $d->refresh()->cost_ar);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $d = $this->fiche('fiche-simulee');
        $path = $this->writeJson([['slug' => 'fiche-simulee', 'en' => ['institution' => 'Town hall']]]);

        $this->artisan('demarches:import-translations', ['file' => $path, '--dry-run' => true])->assertSuccessful();

        $this->assertNull($d->refresh()->institution_en);
    }
}
