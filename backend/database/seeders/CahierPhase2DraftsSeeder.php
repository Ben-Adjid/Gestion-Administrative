<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Demarche;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use UnexpectedValueException;

/**
 * Crée en brouillon les démarches de la phase 2 listées au cahier des charges v2.0.
 * Le cahier ne fournit ni pièces, ni coûts, ni étapes pour elles : aucune fiche n'est publiée
 * et les champs de contenu restent à compléter par un administrateur.
 */
class CahierPhase2DraftsSeeder extends Seeder
{
    private const PENDING_TEXT = 'À renseigner : contenu non fourni par le cahier des charges v2.0 (phase 2).';

    public function run(): void
    {
        $records = json_decode(
            file_get_contents(database_path('data/cahier_phase2.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($records) || count($records) !== 12) {
            throw new UnexpectedValueException('La liste phase 2 du cahier doit contenir 12 démarches.');
        }

        foreach ($records as $record) {
            $categorie = Categorie::query()->where('slug', $record['category'])->first();

            if (!$categorie) {
                throw new UnexpectedValueException("Catégorie absente pour « {$record['title']} » : {$record['category']}.");
            }

            $slug = Str::slug($record['title']);

            // Une fiche déjà créée (brouillon ou publiée par un administrateur) n'est jamais réécrite.
            Demarche::withTrashed()->firstOrCreate(
                ['slug' => $slug],
                [
                    'categorie_id' => $categorie->id,
                    'title' => $record['title'],
                    'summary' => self::PENDING_TEXT,
                    'description' => self::draftDescription($record),
                    'institution' => 'À renseigner',
                    'cost' => 'À renseigner',
                    'status' => 'draft',
                    'is_online' => false,
                ]
            );
        }

        $this->command?->info('12 démarches de la phase 2 créées en brouillon.');
    }

    /**
     * @param  array{verification: string, status: string, sources: array<int, string>}  $record
     */
    private static function draftDescription(array $record): string
    {
        $sources = $record['sources'] === []
            ? 'Aucune source officielle trouvée.'
            : 'Sources : ' . implode(' ; ', $record['sources']);

        return "Statut de vérification : {$record['status']} (consulté le 03/11/2026). {$record['verification']} {$sources}";
    }
}
