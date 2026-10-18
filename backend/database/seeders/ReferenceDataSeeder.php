<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Demarche;
use App\Models\DocumentValidityRule;
use App\Models\LifeEvent;
use App\Models\Synonyme;
use Illuminate\Database\Seeder;

/**
 * Villes et parcours de vie de départ. Idempotent : ne crée que ce qui manque
 * et ne modifie jamais un enregistrement existant (les corrections de l'équipe sont conservées).
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('data/reference.php');

        foreach ($data['cities'] as $slug => [$name, $nameAr, $province, $order]) {
            City::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'name_ar' => $nameAr, 'province' => $province, 'display_order' => $order, 'is_active' => true,
            ]);
        }

        foreach ($data['synonyms'] as [$term, $synonym]) {
            Synonyme::firstOrCreate(['term' => $term, 'synonym' => $synonym], ['weight' => 5, 'is_active' => true]);
        }

        foreach ($data['life_events'] as $slug => $event) {
            if (LifeEvent::where('slug', $slug)->exists()) {
                continue;
            }

            $ids = Demarche::whereIn('slug', $event['steps'])->pluck('id', 'slug');
            // Un parcours n'est créé que si toutes ses fiches existent (aucun parcours à trous).
            if ($ids->count() !== count($event['steps'])) {
                continue;
            }

            $lifeEvent = LifeEvent::create([
                'slug' => $slug,
                'title' => $event['title'][0], 'title_en' => $event['title'][1], 'title_ar' => $event['title'][2],
                'description' => $event['description'][0], 'description_en' => $event['description'][1], 'description_ar' => $event['description'][2],
                'icon' => $event['icon'], 'display_order' => $event['order'], 'is_active' => true,
            ]);

            foreach ($event['steps'] as $position => $demarcheSlug) {
                $lifeEvent->demarches()->attach($ids[$demarcheSlug], ['position' => $position + 1]);
            }
        }

        // Règles de validité des documents (sourcées ou « à vérifier ») : créées si absentes, jamais écrasées.
        foreach (require database_path('data/document_validity.php') as $rule) {
            if (DocumentValidityRule::where('key', $rule['key'])->exists()) {
                continue;
            }

            DocumentValidityRule::create([
                'key' => $rule['key'],
                'name' => $rule['name'][0], 'name_en' => $rule['name'][1], 'name_ar' => $rule['name'][2],
                'country_code' => 'TD',
                'category' => $rule['category'],
                'demarche_id' => $rule['demarche'] ? Demarche::where('slug', $rule['demarche'])->value('id') : null,
                'validity_months' => $rule['validity_months'],
                'validity_start' => $rule['validity_start'],
                'renewal' => $rule['renewal'][0], 'renewal_en' => $rule['renewal'][1], 'renewal_ar' => $rule['renewal'][2],
                'notes' => $rule['notes'][0], 'notes_en' => $rule['notes'][1], 'notes_ar' => $rule['notes'][2],
                'status' => $rule['status'],
                'source_label' => $rule['source_label'], 'source_url' => $rule['source_url'],
                'source_date' => $rule['source_date'], 'verified_on' => $rule['verified_on'],
                'position' => $rule['position'],
                'is_active' => true,
            ]);
        }
    }
}
