<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Categorie;
use App\Models\Demarche;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Remet le catalogue en conformité avec le cahier des charges :
 * catégories officielles, slugs SEO valides, doublons archivés, fiches bien classées,
 * traductions EN/AR complétées (uniquement les champs vides).
 * Aucune suppression définitive : les doublons sont archivés (soft delete, restaurables).
 */
class NormalizeCatalogue extends Command
{
    protected $signature = 'catalogue:normalize {--apply : Écrire les changements (sinon simulation)}';

    protected $description = 'Normalise catégories, slugs, doublons et traductions du catalogue';

    private array $report = [];

    public function handle(): int
    {
        // L'instance de commande est réutilisée par Artisan::call : le rapport doit repartir de zéro.
        $this->report = [];
        $data = require database_path('data/catalogue.php');
        $cahierDemarches = json_decode(
            file_get_contents(database_path('data/cahier_demarches.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $canonicalSlugs = [];
        foreach ($cahierDemarches as $record) {
            $titleSlug = Str::slug($record['title']);
            $canonicalSlug = $record['slug'] ?? $titleSlug;
            $canonicalSlugs[$titleSlug] = $canonicalSlug;
            $data['placement'][$canonicalSlug] = $record['category'];
        }
        $apply = (bool) $this->option('apply');

        DB::beginTransaction();
        try {
            $categoryIds = $this->normalizeCategories($data['categories'], $data['legacy_category_names'] ?? []);
            $this->mergeLegacyCategoryDemarches($data['categories'], $categoryIds);
            $this->fillCategoryDescriptions($data['category_descriptions'], $data['obsolete_category_descriptions'], $categoryIds);
            $this->normalizeDemarches($data['placement'], $categoryIds, $canonicalSlugs);
            $this->fillTranslations($data['translations'], $data['translation_fields'] ?? []);
            $this->deactivateEmptyLegacyCategories(array_keys($data['categories']));

            if ($apply) {
                if ($this->report) {
                    ActivityLog::logAction(null, 'catalogue_normalize', 'Normalisation du catalogue : ' . count($this->report) . ' changement(s)');
                }
                DB::commit();
            } else {
                DB::rollBack();
            }
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

    /** @return array<string, int> slug => id */
    private function normalizeCategories(array $definitions, array $legacyNames): array
    {
        $ids = [];
        foreach ($definitions as $slug => [$fr, $en, $ar, $icon, $color, $order, $legacy]) {
            $candidates = array_merge([$slug], $legacy);
            $category = Categorie::withTrashed()->whereIn('slug', $candidates)->orderByRaw('slug = ? desc', [$slug])->first();

            $attributes = [
                'name' => $fr, 'name_en' => $en, 'name_ar' => $ar, 'slug' => $slug,
                'icon' => $icon, 'color' => $color, 'display_order' => $order, 'is_active' => true,
            ];

            if (!$category) {
                $category = Categorie::create($attributes);
                $this->report[] = "Catégorie créée : {$fr} ({$slug})";
            } elseif ($category->slug === $slug && !$category->trashed()) {
                // Catégorie déjà en place : les choix de l'équipe (nom, icône, couleur, ordre, visibilité) sont conservés ;
                // seules les traductions vides sont complétées.
                $oldNames = $legacyNames[$slug] ?? [];
                foreach (['name', 'name_en', 'name_ar'] as $index => $field) {
                    if (isset($oldNames[$index]) && $category->{$field} === $oldNames[$index]) {
                        $category->{$field} = [$fr, $en, $ar][$index];
                    }
                }
                foreach (['name_en' => $en, 'name_ar' => $ar] as $field => $value) {
                    if (blank($category->{$field})) {
                        $category->{$field} = $value;
                    }
                }
                if ($category->isDirty()) {
                    $this->report[] = "Traduction du nom complétée : {$category->name}";
                    $category->save();
                }
            } else {
                // Ancienne catégorie (slug historique) ou archivée : fusionnée vers la catégorie du cahier des charges.
                $category->fill($attributes);
                if ($category->trashed()) {
                    $category->restore();
                }
                if ($category->isDirty()) {
                    $this->report[] = "Catégorie mise à jour : {$category->getOriginal('name')} ({$category->getOriginal('slug')}) → {$fr} ({$slug})";
                    $category->save();
                }
            }
            $ids[$slug] = $category->id;
        }

        return $ids;
    }

    /** Regroupe les démarches des anciennes catégories sous les catégories officielles sans supprimer de fiches. */
    private function mergeLegacyCategoryDemarches(array $definitions, array $categoryIds): void
    {
        foreach ($definitions as $slug => $definition) {
            $targetId = $categoryIds[$slug] ?? null;
            if (!$targetId) {
                continue;
            }

            foreach ($definition[6] ?? [] as $legacySlug) {
                $legacy = Categorie::withTrashed()->where('slug', $legacySlug)->first();
                if (!$legacy || $legacy->id === $targetId) {
                    continue;
                }

                $moved = DB::table('demarches')->where('categorie_id', $legacy->id)->update(['categorie_id' => $targetId]);
                if ($moved > 0) {
                    $this->report[] = "{$moved} démarche(s) déplacée(s) de {$legacy->name} vers {$definition[0]}";
                }
                if ($legacy->is_active && !DB::table('demarches')->where('categorie_id', $legacy->id)->exists()) {
                    $legacy->update(['is_active' => false]);
                    $this->report[] = "Ancienne catégorie désactivée : {$legacy->name} ({$legacySlug})";
                }
            }
        }
    }

    /** Complète les descriptions vides ou obsolètes ; une description saisie par l'équipe n'est jamais écrasée. */
    private function fillCategoryDescriptions(array $descriptions, array $obsolete, array $categoryIds): void
    {
        foreach ($descriptions as $slug => $values) {
            $category = isset($categoryIds[$slug]) ? Categorie::find($categoryIds[$slug]) : null;
            if (!$category) {
                continue;
            }
            foreach (array_combine(['description', 'description_en', 'description_ar'], $values) as $field => $value) {
                $current = trim((string) $category->{$field});
                if ($current === '' || in_array($current, $obsolete, true)) {
                    $category->{$field} = $value;
                }
            }
            if ($category->isDirty()) {
                $this->report[] = "Description de catégorie complétée : {$category->name}";
                $category->save();
            }
        }
    }

    private function normalizeDemarches(array $placement, array $categoryIds, array $canonicalSlugs): void
    {
        $groups = Demarche::orderBy('id')->get()->groupBy(fn (Demarche $d) => Str::slug($d->title));

        foreach ($groups as $titleSlug => $demarches) {
            $slug = $canonicalSlugs[$titleSlug] ?? $titleSlug;
            $keeper = $demarches->first();

            foreach ($demarches->slice(1) as $duplicate) {
                $duplicate->slug = "{$slug}-doublon-{$duplicate->id}";
                $duplicate->save();
                $duplicate->delete();
                $this->report[] = "Doublon archivé : « {$duplicate->title} » #{$duplicate->id} (conservé : #{$keeper->id})";
            }

            // Montants français : « 25,000 FCFA » -> « 25 000 FCFA » (espace insécable : pas de coupure de ligne).
            $frenchCost = preg_replace('/(\d),(\d{3})(?!\d)/u', "$1\u{00A0}$2", (string) $keeper->cost);
            if ($frenchCost !== $keeper->cost) {
                $this->report[] = "Coût reformaté : {$keeper->cost} → {$frenchCost} ({$keeper->title})";
                $keeper->cost = $frenchCost;
            }

            // Montant numérique déduit d'un prix unique (« 5 000 FCFA », « Gratuit ») ; les fourchettes restent vides.
            if ($keeper->cost_amount === null) {
                $amount = Demarche::amountFromCost($keeper->cost);
                if ($amount !== null) {
                    $keeper->cost_amount = $amount;
                    $this->report[] = "Montant numérique renseigné : {$amount} FCFA ({$keeper->title})";
                }
            }

            if ($keeper->slug !== $slug) {
                $this->report[] = "Slug corrigé : {$keeper->slug} → {$slug}";
                $keeper->slug = $slug;
            }

            if (isset($placement[$slug], $categoryIds[$placement[$slug]]) && $keeper->categorie_id !== $categoryIds[$placement[$slug]]) {
                $this->report[] = "« {$keeper->title} » déplacée vers la catégorie {$placement[$slug]}";
                $keeper->categorie_id = $categoryIds[$placement[$slug]];
            }

            $keeper->save();
        }
    }

    private function fillTranslations(array $translations, array $allowedFields = []): void
    {
        foreach ($translations as $slug => $languages) {
            $demarche = Demarche::where('slug', $slug)->first();
            if (!$demarche) {
                // Simple information : rien n'est modifié, donc rien n'est inscrit au rapport ni au journal.
                $this->warn("Traduction ignorée : aucune fiche « {$slug} »");
                continue;
            }

            $filled = [];
            foreach ($languages as $language => $fields) {
                foreach ($fields as $field => $value) {
                    if ($allowedFields && !in_array($field, $allowedFields, true)) {
                        continue;
                    }

                    $column = "{$field}_{$language}";
                    $current = $demarche->getAttribute($column);
                    $isEmpty = is_array($current) ? count($current) === 0 : blank($current);
                    if ($isEmpty) {
                        $demarche->setAttribute($column, $value);
                        $filled[$language] = true;
                    }
                }
            }

            if ($filled) {
                $demarche->save();
                $this->report[] = "Traductions ajoutées (" . implode(', ', array_keys($filled)) . ") : {$demarche->title}";
            }
        }
    }

    private function deactivateEmptyLegacyCategories(array $officialSlugs): void
    {
        Categorie::whereNotIn('slug', $officialSlugs)->where('is_active', true)->get()
            ->filter(fn (Categorie $c) => $c->demarches()->count() === 0)
            ->each(function (Categorie $c) {
                $c->update(['is_active' => false]);
                $this->report[] = "Catégorie vide désactivée : {$c->name} ({$c->slug})";
            });
    }
}
