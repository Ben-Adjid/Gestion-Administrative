<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Demarche;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use UnexpectedValueException;

/**
 * Crée en brouillon les 8 démarches proposées (hors cahier des charges), avec leur statut réel de vérification.
 * Aucune n'est publiée : les champs de contenu restent « À renseigner » tant qu'une source officielle n'est pas trouvée.
 */
class CahierProposalsDraftsSeeder extends Seeder
{
    private const PENDING_TEXT = 'À renseigner : aucune source officielle tchadienne confirmée.';
    private const CHECKED_ON = '03/11/2026';

    public function run(): void
    {
        $records = json_decode(
            file_get_contents(database_path('data/cahier_proposals.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($records) || count($records) !== 8) {
            throw new UnexpectedValueException('La liste des 8 propositions doit contenir 8 démarches.');
        }

        foreach ($records as $record) {
            $categorie = Categorie::query()->where('slug', $record['category'])->first();

            if (!$categorie) {
                throw new UnexpectedValueException("Catégorie absente pour « {$record['title']} » : {$record['category']}.");
            }

            Demarche::withTrashed()->firstOrCreate(
                ['slug' => Str::slug($record['title'])],
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

        $this->command?->info('8 démarches proposées créées en brouillon.');
    }

    /**
     * @param  array{title: string, status: string, verification: string, sources: array<int, string>}  $record
     */
    private static function draftDescription(array $record): string
    {
        $sources = $record['sources'] === []
            ? 'Aucune source officielle trouvée.'
            : 'Sources : ' . implode(' ; ', $record['sources']);

        return 'Proposition hors cahier des charges. Statut de vérification : ' . $record['status']
            . ' (consulté le ' . self::CHECKED_ON . '). ' . $record['verification'] . ' ' . $sources;
    }
}
