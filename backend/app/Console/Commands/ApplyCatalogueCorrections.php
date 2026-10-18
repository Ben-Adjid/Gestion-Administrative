<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\City;
use App\Models\Demarche;
use App\Models\Office;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Applique les corrections sourcées de database/data/corrections.php.
 * Idempotent ; chaque modification est consignée avec ses anciennes valeurs (restauration possible).
 */
class ApplyCatalogueCorrections extends Command
{
    protected $signature = 'catalogue:corrections {--apply : Écrit les corrections (sinon simple simulation)}';

    protected $description = 'Applique les corrections sourcées du catalogue et retire les coordonnées de démonstration invérifiables';

    private array $report = [];

    public function handle(): int
    {
        $this->report = [];
        $data = require database_path('data/corrections.php');
        $apply = (bool) $this->option('apply');

        DB::beginTransaction();
        try {
            $this->correctDemarches($data['demarches']);
            $this->removeUnverifiedContacts($data['unverified_contacts']);
            $this->createOffices($data['offices']);
            $this->setAuthorities($data['authorities']);
            $this->applyTextFixes($data['text_fixes']);

            $apply ? DB::commit() : DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        foreach ($this->report as $line) {
            $this->line(' - ' . $line);
        }
        $this->info(count($this->report) . ' changement(s) ' . ($apply ? 'appliqué(s).' : 'simulé(s). Relancez avec --apply pour les écrire.'));

        return self::SUCCESS;
    }

    private function correctDemarches(array $corrections): void
    {
        foreach ($corrections as $slug => $fields) {
            $demarche = Demarche::where('slug', $slug)->first();
            if (!$demarche) {
                continue;
            }

            $old = [];
            foreach ($fields as $field => $value) {
                if ($demarche->getAttribute($field) != $value) {
                    $old[$field] = $demarche->getAttribute($field);
                    $demarche->setAttribute($field, $value);
                }
            }
            if (!$old) {
                continue;
            }

            $demarche->save();
            ActivityLog::logAction(null, 'catalogue_correction', Str::limit("Correction sourcée de la fiche « {$demarche->title} » (" . count($old) . ' champ(s))', 250),
                Demarche::class, $demarche->id, $old, array_intersect_key($fields, $old));
            $this->report[] = "Fiche corrigée : {$demarche->title} (" . implode(', ', array_keys($old)) . ')';
        }
    }

    private function removeUnverifiedContacts(array $contacts): void
    {
        $demarches = Demarche::withTrashed()
            ->where(fn ($q) => $q->whereIn('contact_phone', $contacts['phones'])->orWhereIn('contact_email', $contacts['emails']))
            ->get();

        foreach ($demarches as $demarche) {
            $old = [];
            if (in_array($demarche->contact_phone, $contacts['phones'], true)) {
                $old['contact_phone'] = $demarche->contact_phone;
                $demarche->contact_phone = null;
            }
            if (in_array($demarche->contact_email, $contacts['emails'], true)) {
                $old['contact_email'] = $demarche->contact_email;
                $demarche->contact_email = null;
            }
            if (!$old) {
                continue;
            }

            $demarche->save();
            ActivityLog::logAction(null, 'catalogue_correction', "Coordonnées invérifiables retirées de la fiche « {$demarche->title} »",
                Demarche::class, $demarche->id, $old, array_fill_keys(array_keys($old), null));
            $this->report[] = "Coordonnées retirées : {$demarche->title}" . ($demarche->trashed() ? ' [fiche archivée]' : '') . ' (' . implode(', ', array_values($old)) . ')';
        }
    }

    private function createOffices(array $offices): void
    {
        foreach ($offices as $item) {
            $city = City::where('slug', $item['city'])->first();
            if (!$city || Office::where('city_id', $city->id)->where('name', $item['name'])->exists()) {
                continue;
            }

            $office = Office::create([
                'city_id' => $city->id,
                'name' => $item['name'], 'name_en' => $item['name_en'], 'name_ar' => $item['name_ar'],
                'source_url' => $item['source_url'], 'is_active' => true,
            ]);
            $office->demarches()->sync(Demarche::whereIn('slug', $item['demarches'])->pluck('id'));
            ActivityLog::logAction(null, 'catalogue_correction', "Bureau sourcé ajouté : {$office->name} ({$city->name})", Office::class, $office->id);
            $this->report[] = "Bureau ajouté : {$office->name} · {$city->name}";
        }
    }

    /** Remplacements ciblés dans des champs liste (étapes, pièces) ; sans effet si le texte a déjà été corrigé. */
    private function applyTextFixes(array $fixes): void
    {
        foreach ($fixes as $slug => $fields) {
            $demarche = Demarche::withTrashed()->where('slug', $slug)->first();
            if (!$demarche) {
                continue;
            }

            $old = [];
            $new = [];
            foreach ($fields as $field => $replacements) {
                // Champ texte (lieu, institution…) ou liste (étapes, pièces) : le remplacement s'applique aux deux.
                $value = $demarche->getAttribute($field);
                if (is_string($value)) {
                    $items = $value;
                    $fixed = strtr($value, $replacements);
                } else {
                    $items = (array) $value;
                    $fixed = array_map(fn ($item) => is_string($item) ? strtr($item, $replacements) : $item, $items);
                }
                if ($fixed !== $items) {
                    $old[$field] = $items;
                    $new[$field] = $fixed;
                    $demarche->setAttribute($field, $fixed);
                }
            }
            if (!$new) {
                continue;
            }

            $demarche->save();
            ActivityLog::logAction(null, 'catalogue_correction', Str::limit("Montant corrigé dans « {$demarche->title} » (" . implode(', ', array_keys($new)) . ')', 250),
                Demarche::class, $demarche->id, $old, $new);
            $this->report[] = "Texte corrigé : {$demarche->title} (" . implode(', ', array_keys($new)) . ')';
        }
    }

    /** Renseigne l'organisme compétent et la ville du texte « lieu » ; ne remplace jamais un choix déjà fait par l'équipe. */
    private function setAuthorities(array $authorities): void
    {
        foreach ($authorities as $slug => $item) {
            $demarche = Demarche::withTrashed()->where('slug', $slug)->first();
            if (!$demarche) {
                continue;
            }

            $changes = [];
            if ($demarche->authority_level === null && $item['level'] !== null) {
                $changes['authority_level'] = $item['level'];
            }
            if ($demarche->location_city_id === null && $item['location_city'] !== null && filled($demarche->location)) {
                $cityId = City::where('slug', $item['location_city'])->value('id');
                if ($cityId) {
                    $changes['location_city_id'] = $cityId;
                }
            }
            if (!$changes) {
                continue;
            }

            $demarche->forceFill($changes)->save();
            ActivityLog::logAction(null, 'catalogue_correction', "Organisme compétent et ville du lieu renseignés pour « {$demarche->title} »",
                Demarche::class, $demarche->id, array_fill_keys(array_keys($changes), null), $changes);
            $this->report[] = "Organisme/lieu : {$demarche->title} (" . implode(', ', array_map(fn ($k, $v) => "$k=$v", array_keys($changes), $changes)) . ')';
        }
    }
}
