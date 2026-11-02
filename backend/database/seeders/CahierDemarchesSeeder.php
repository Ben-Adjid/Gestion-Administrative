<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Demarche;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnexpectedValueException;

class CahierDemarchesSeeder extends Seeder
{
    private const LEGACY_DEMO_SLUGS = [
        'consultation-medicale',
        'inscription-sur-la-liste-electorale-ceni',
    ];

    private const MINISTRIES = [
        'identite' => 'Ministère de la Sécurité publique / Direction de la Police nationale',
        'etat-civil' => "Ministère de l'Administration du territoire / Mairies",
        'voyage' => 'Ministère des Affaires étrangères / DGDI',
        'etudes' => "Ministère de l'Éducation nationale / Ministère de l'Enseignement supérieur",
        'travail' => 'Ministère du Travail / CNPS / ANPE',
        'sante' => 'Ministère de la Santé publique / Ministère des Affaires sociales',
        'justice' => 'Ministère de la Justice / Tribunaux / Parquet',
        'legalisations' => 'Ministère des Affaires étrangères / Cadastre / Notaires',
        'commerce' => 'Ministère du Commerce / Greffe du Tribunal de commerce / ANPME',
        'transport' => 'Ministère des Transports terrestres / Direction des transports',
    ];

    public function run(): void
    {
        $records = json_decode(
            file_get_contents(database_path('data/cahier_demarches.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($records) || count($records) !== 80) {
            throw new UnexpectedValueException('Le catalogue du cahier doit contenir exactement 80 fiches.');
        }

        $categoryIds = Categorie::query()
            ->whereIn('slug', array_unique(array_column($records, 'category')))
            ->pluck('id', 'slug')
            ->all();
        $slugs = [];
        $numbers = [];

        foreach ($records as $record) {
            $slug = $record['slug'] ?? Str::slug($record['title'] ?? '');

            if (
                !$slug
                || !isset($categoryIds[$record['category'] ?? ''])
                || empty($record['title'])
                || empty($record['location'])
                || empty($record['cost'])
                || empty($record['processing_time'])
                || empty($record['validity'])
                || empty($record['required_documents'])
                || empty($record['conditions'])
                || empty($record['procedure_steps'])
            ) {
                throw new UnexpectedValueException('Une fiche du cahier est incomplète ou référence une catégorie absente.');
            }

            if (isset($slugs[$slug]) || isset($numbers[$record['number']])) {
                throw new UnexpectedValueException('Le catalogue du cahier contient un slug ou un numéro en double.');
            }

            $slugs[$slug] = true;
            $numbers[$record['number']] = true;
        }

        // Ministères responsables, repris des en-têtes de chapitre du cahier des charges v2.0.
        foreach (self::MINISTRIES as $categorySlug => $ministry) {
            Categorie::query()->where('slug', $categorySlug)->update(['ministry' => $ministry]);
        }

        DB::transaction(function () use ($records, $categoryIds, $slugs): void {
            foreach ($records as $record) {
                $slug = $record['slug'] ?? Str::slug($record['title']);
                $summary = "Démarche administrative au Tchad : {$record['title']}.";
                $description = sprintf(
                    'Fiche extraite du cahier des charges DemarchesTchad v2.0, fiche n°%d (page %d). Les pièces, conditions et étapes ci-dessous reprennent le document. Les informations peuvent évoluer et sont à confirmer auprès du service compétent.',
                    $record['number'],
                    $record['source_page']
                );
                $existing = Demarche::withTrashed()->where('slug', $slug)->first();
                // Empreinte de la fiche source : seule une modification du cahier réécrit la fiche.
                $sourceHash = hash('sha256', json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                $sourceContentChanged = !$existing || $existing->source_hash !== $sourceHash;
                $translationAttributes = [];

                foreach (Demarche::TRANSLATABLE as $field) {
                    foreach (['en', 'ar'] as $language) {
                        $translationAttributes["{$field}_{$language}"] = null;
                    }
                }

                $costAmount = self::amountFromDocumentedCost($record['cost']);
                $attributes = [
                    'categorie_id' => $categoryIds[$record['category']],
                    'title' => $record['title'],
                    'summary' => $summary,
                    'description' => $description,
                    'required_documents' => $record['required_documents'],
                    'procedure_steps' => $record['procedure_steps'],
                    'conditions' => $record['conditions'],
                    'cost' => $record['cost'],
                    'cost_amount' => $costAmount,
                    'extra_costs' => [],
                    'processing_time' => $record['processing_time'],
                    'processing_time_days' => null,
                    'validity' => $record['validity'],
                    'institution' => $record['institution'],
                    'location' => $record['location'],
                    'status' => 'published',
                    'is_online' => false,
                    'online_url' => null,
                    'source_hash' => $sourceHash,
                ];

                // Fiche déjà importée et inchangée : on ne réécrit rien. Ainsi les corrections sourcées
                // de l'équipe (catalogue:corrections) ne sont ni écrasées ni ré-enregistrées à chaque relance.
                if ($existing && !$sourceContentChanged) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    continue;
                }

                if ($sourceContentChanged) {
                    $attributes = array_merge($attributes, $translationAttributes, ['last_verified_at' => null]);
                }

                $demarche = Demarche::withTrashed()->updateOrCreate(
                    ['slug' => $slug],
                    $attributes
                );

                if ($demarche->trashed()) {
                    $demarche->restore();
                }
            }

            Demarche::query()
                ->whereIn('slug', self::LEGACY_DEMO_SLUGS)
                ->whereNotIn('slug', array_keys($slugs))
                ->update(['status' => 'archived']);
        });

        $this->command?->info('80 démarches du cahier ont été importées.');
    }

    private static function amountFromDocumentedCost(string $cost): ?int
    {
        if (preg_match('/^Gratuit$/iu', trim($cost))) {
            return 0;
        }

        if (preg_match('/^(\d+(?:[\s\x{00A0}\x{202F},.]\d{3})*)\s*FCFA(?:\s*\([^)]*\))?$/iu', trim($cost), $matches)) {
            return (int) preg_replace('/\D/u', '', $matches[1]);
        }

        return null;
    }
}
