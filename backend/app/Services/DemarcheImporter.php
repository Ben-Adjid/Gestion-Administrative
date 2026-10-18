<?php

namespace App\Services;

use App\Models\Categorie;
use App\Models\Demarche;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Import de démarches depuis un tableur (.xlsx) ou un fichier CSV.
 * Chaque ligne devient un BROUILLON : rien n'est publié sans relecture par un administrateur.
 * L'import est « tout ou rien » : s'il reste une erreur, aucune ligne n'est enregistrée.
 */
class DemarcheImporter
{
    public const MAX_ROWS = 500;

    /** Colonnes du modèle : clé interne => [en-tête du tableur, obligatoire] */
    public const COLUMNS = [
        'title' => ['titre', true],
        'categorie' => ['categorie', true],
        'institution' => ['institution', true],
        'cost' => ['cout', true],
        'description' => ['description', true],
        'processing_time' => ['delai', false],
        'summary' => ['resume', false],
        'required_documents' => ['pieces', false],
        'procedure_steps' => ['etapes', false],
        'conditions' => ['conditions', false],
        'location' => ['lieu', false],
        'contact_phone' => ['telephone', false],
        'contact_email' => ['email', false],
        'official_source_url' => ['source', false],
    ];

    private const LIST_FIELDS = ['required_documents', 'procedure_steps', 'conditions'];

    /** Lit le fichier et renvoie les lignes sous forme de tableaux [en-tête => valeur]. */
    public function readRows(string $path, string $extension): array
    {
        $rows = strtolower($extension) === 'xlsx' ? $this->readXlsx($path) : $this->readCsv($path);
        $rows = array_values(array_filter($rows, fn (array $row) => count(array_filter($row, fn ($v) => trim((string) $v) !== '')) > 0));
        if (!$rows) {
            return [];
        }

        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), array_shift($rows));

        return array_map(function (array $row) use ($headers) {
            $assoc = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $assoc[$header] = trim((string) ($row[$index] ?? ''));
                }
            }

            return $assoc;
        }, $rows);
    }

    /**
     * Analyse les lignes : données prêtes à créer et erreurs par ligne (numéro de ligne du tableur).
     *
     * @return array{valid: array<int, array>, errors: array<int, string[]>}
     */
    public function analyse(array $rows): array
    {
        $categories = Categorie::all();
        $existingTitles = Demarche::withTrashed()->pluck('title')->map(fn ($t) => Str::lower(trim($t)))->flip();
        $seen = [];
        $valid = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // ligne 1 = en-têtes
            $lineErrors = [];
            $data = [];

            foreach (self::COLUMNS as $field => [$header, $required]) {
                $value = $row[$header] ?? '';
                if ($required && $value === '') {
                    $lineErrors[] = __('messages.import_missing_field', ['field' => $header]);
                }
                $data[$field] = $value;
            }

            $category = $categories->first(fn ($c) => Str::lower($c->slug) === Str::lower($data['categorie'])
                || Str::lower($c->name) === Str::lower($data['categorie']));
            if ($data['categorie'] !== '' && !$category) {
                $lineErrors[] = __('messages.import_unknown_category', ['value' => $data['categorie']]);
            }

            $titleKey = Str::lower($data['title']);
            if ($data['title'] !== '' && ($existingTitles->has($titleKey) || isset($seen[$titleKey]))) {
                $lineErrors[] = __('messages.import_duplicate');
            }
            $seen[$titleKey] = true;

            foreach ([['title', 255], ['institution', 255], ['cost', 255], ['processing_time', 255], ['location', 255], ['contact_phone', 50], ['contact_email', 255]] as [$field, $max]) {
                if (mb_strlen($data[$field]) > $max) {
                    $lineErrors[] = __('validation.max.string', ['attribute' => self::COLUMNS[$field][0], 'max' => $max]);
                }
            }
            if ($data['contact_email'] !== '' && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
                $lineErrors[] = __('validation.email', ['attribute' => 'email']);
            }
            if ($data['official_source_url'] !== '' && !filter_var($data['official_source_url'], FILTER_VALIDATE_URL)) {
                $lineErrors[] = __('validation.url', ['attribute' => 'source']);
            }

            if ($lineErrors) {
                $errors[$line] = $lineErrors;
                continue;
            }

            foreach (self::LIST_FIELDS as $field) {
                // Une valeur par ligne dans la cellule, ou séparée par « ; »
                $data[$field] = array_values(array_filter(array_map('trim', preg_split('/\R|;/u', $data[$field]))));
            }
            $data['categorie_id'] = $category->id;
            unset($data['categorie']);
            $valid[$line] = $data;
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /** Crée les brouillons. Renvoie le nombre de démarches créées. */
    public function create(array $valid, int $adminId): int
    {
        foreach ($valid as $data) {
            $slugBase = Str::slug($data['title']) ?: 'demarche';
            $slug = $slugBase;
            for ($i = 2; Demarche::withTrashed()->where('slug', $slug)->exists(); $i++) {
                $slug = "{$slugBase}-{$i}";
            }

            Demarche::create(array_map(fn ($v) => $v === '' ? null : $v, $data) + [
                'slug' => $slug,
                'cost_amount' => Demarche::amountFromCost($data['cost']),
                'status' => 'draft',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
        }

        return count($valid);
    }

    /** Écrit le modèle .xlsx (en-têtes + une ligne d'exemple à remplacer). */
    public function writeTemplate(string $path): void
    {
        $writer = new XlsxWriter();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(array_map(fn ($c) => $c[0], self::COLUMNS)));
        $writer->addRow(Row::fromValues([
            'Exemple : légalisation de signature', 'legalisations', 'Mairie de la commune', '500 FCFA',
            'Faire certifier une signature sur un document. Remplacez cette ligne par vos démarches.',
            '1 jour', 'Faire légaliser une signature', "Pièce d'identité ; Document à légaliser",
            'Se présenter au guichet ; Payer les frais ; Retirer le document', '', 'Mairie', '', '', '',
        ]));
        $writer->close();
    }

    private function readXlsx(string $path): array
    {
        $reader = new XlsxReader();
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value, $row->toArray());
                if (count($rows) > self::MAX_ROWS + 1) {
                    break;
                }
            }
            break; // première feuille uniquement
        }
        $reader->close();

        return $rows;
    }

    private function readCsv(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // BOM d'Excel
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252'); // CSV Excel français
        }
        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false && count($rows) <= self::MAX_ROWS + 1) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)->ascii()->lower()->replaceMatches('/[^a-z]/', '')->toString();
    }
}
