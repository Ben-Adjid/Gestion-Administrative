<?php

namespace App\Console\Commands;

use App\Models\Demarche;
use Illuminate\Console\Command;

/**
 * Importe les traductions anglaises et arabes des fiches depuis un fichier JSON, identifiées par slug.
 * Par défaut, un champ déjà renseigné n'est pas remplacé ; --overwrite force la mise à jour.
 */
class ImportDemarcheTranslations extends Command
{
    protected $signature = 'demarches:import-translations
        {file : Fichier JSON (chemin relatif au dossier backend, ou absolu)}
        {--overwrite : Remplace aussi les traductions déjà renseignées}
        {--dry-run : Compte ce qui serait écrit, sans rien enregistrer}';

    protected $description = 'Importe les traductions EN/AR des fiches depuis un fichier JSON (par slug).';

    private const TEXT = ['title', 'summary', 'description', 'cost', 'processing_time', 'validity', 'institution', 'location'];
    private const LIST = ['required_documents', 'procedure_steps', 'conditions'];

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (!is_file($path)) {
            $path = base_path($path);
        }
        if (!is_file($path)) {
            $this->error('Fichier introuvable.');
            return self::FAILURE;
        }

        $items = json_decode((string) file_get_contents($path), true);
        if (!is_array($items)) {
            $this->error('Fichier JSON invalide : un tableau de fiches est attendu.');
            return self::FAILURE;
        }

        $written = 0;
        $kept = 0;
        $notFound = [];

        foreach ($items as $item) {
            $slug = $item['slug'] ?? null;
            $demarche = $slug ? Demarche::withTrashed()->where('slug', $slug)->first() : null;
            if (!$demarche) {
                $notFound[] = (string) $slug;
                continue;
            }

            $changes = [];
            foreach (['en', 'ar'] as $language) {
                foreach (array_merge(self::TEXT, self::LIST) as $field) {
                    $value = $item[$language][$field] ?? null;
                    if ($value === null || $value === '' || $value === []) {
                        continue;
                    }
                    if (in_array($field, self::LIST, true)) {
                        $value = array_values((array) $value);
                    }
                    $column = "{$field}_{$language}";
                    $current = $demarche->getAttribute($column);
                    $filled = is_array($current) ? count($current) > 0 : filled($current);
                    if ($filled && !$this->option('overwrite')) {
                        $kept++;
                        continue;
                    }
                    $changes[$column] = $value;
                }
            }

            if ($changes !== [] && !$this->option('dry-run')) {
                $demarche->forceFill($changes)->save();
            }
            $written += count($changes);
        }

        $this->info("Champs écrits : {$written} · déjà renseignés (conservés) : {$kept}" . ($this->option('dry-run') ? ' · simulation' : ''));
        if ($notFound !== []) {
            $this->warn('Slugs introuvables : ' . implode(', ', $notFound));
        }

        return self::SUCCESS;
    }
}
