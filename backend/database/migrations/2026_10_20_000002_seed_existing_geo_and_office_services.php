<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reprend uniquement les données déjà présentes, sans rien ajouter d'inventé :
 *  - chaque province citée dans la base devient une unité « à vérifier » ;
 *  - chaque ville devient une localité rattachée à sa province (statut « à vérifier ») ;
 *  - un service n'est rattaché à un bureau que si une source (presse) le mentionne, avec le statut « press_reported » ;
 *  - le centre ANATS d'Ati, signalé par la presse en 2023, est ajouté SANS service rattaché (services non confirmés).
 * Idempotente : relancer la migration ne crée aucun doublon.
 */
return new class extends Migration
{
    private const NOT_VERIFIED = 'Donnée présente dans la base, non vérifiée par une source officielle';

    public function up(): void
    {
        $now = now();

        // 1. Provinces telles qu'elles figurent dans la base.
        $provinces = DB::table('cities')->whereNotNull('province')->where('province', '!=', '')
            ->distinct()->pluck('province');
        foreach ($provinces as $name) {
            $slug = Str::slug($name);
            if (! DB::table('geo_units')->where('slug', $slug)->exists()) {
                DB::table('geo_units')->insert([
                    'level' => 'province', 'name' => $name, 'slug' => $slug, 'status' => 'to_verify',
                    'source_label' => self::NOT_VERIFIED, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // 2. Localités : une par ville, rattachée à sa province, et liée à la ville.
        foreach (DB::table('cities')->get() as $city) {
            $parent = $city->province ? DB::table('geo_units')->where('slug', Str::slug($city->province))->value('id') : null;
            $unitId = DB::table('geo_units')->where('slug', $city->slug)->value('id');
            if ($unitId === null) {
                $unitId = DB::table('geo_units')->insertGetId([
                    'parent_id' => $parent, 'level' => 'locality', 'name' => $city->name, 'name_ar' => $city->name_ar,
                    'slug' => $city->slug, 'status' => 'to_verify', 'source_label' => self::NOT_VERIFIED,
                    'is_active' => (bool) $city->is_active, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            DB::table('cities')->where('id', $city->id)->whereNull('geo_unit_id')->update(['geo_unit_id' => $unitId]);
        }

        // 3. Bureau d'Ati signalé par la presse (2023) : ajouté sans service, car aucune démarche n'est confirmée.
        $ati = DB::table('cities')->where('slug', 'ati')->value('id');
        $atiName = "Centre d'accueil ANATS d'Ati";
        if ($ati && ! DB::table('offices')->where('city_id', $ati)->where('name', $atiName)->exists()) {
            DB::table('offices')->insert([
                'city_id' => $ati, 'name' => $atiName, 'is_active' => true,
                'source_url' => 'https://www.alwihdainfo.com/tchad-un-nouveau-centre-d-accueil-anats-a-ati-pour-soulager-la-population-a121280/',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // 4. Services attestés par la presse, bureau par bureau (aucun autre rattachement).
        $rules = [
            ['city' => 'ndjamena', 'match' => "Centre d'accueil ANATS du % arrondissement", 'demarche' => 'carte-didentite-nationale'],
            ['city' => 'bongor', 'match' => '%Bongor%', 'demarche' => 'passeport-biometrique'],
            ['city' => 'mongo', 'match' => '%Mongo%', 'demarche' => 'passeport-biometrique'],
            ['city' => 'moundou', 'match' => '%état civil%', 'demarche' => 'acte-de-naissance'],
        ];
        foreach ($rules as $rule) {
            $demarcheId = DB::table('demarches')->where('slug', $rule['demarche'])->value('id');
            $cityId = DB::table('cities')->where('slug', $rule['city'])->value('id');
            if (! $demarcheId || ! $cityId) {
                continue;
            }
            foreach (DB::table('offices')->where('city_id', $cityId)->where('name', 'like', $rule['match'])->get() as $office) {
                DB::table('office_services')->updateOrInsert(
                    ['office_id' => $office->id, 'demarche_id' => $demarcheId],
                    [
                        'status' => 'press_reported',
                        'source_label' => 'Article de presse (' . ($office->source_url ? parse_url($office->source_url, PHP_URL_HOST) : 'source inconnue') . ')',
                        'source_url' => $office->source_url,
                        'verified_on' => null,
                        'created_at' => $now, 'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Les tables sont supprimées par la migration de structure ; rien d'autre à annuler.
    }
};
