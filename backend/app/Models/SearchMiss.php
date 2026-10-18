<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Recherche sans résultat (terme seul, aucune donnée personnelle). */
class SearchMiss extends Model
{
    protected $fillable = ['term', 'count', 'last_searched_at', 'dismissed_at'];

    protected $casts = ['count' => 'integer', 'last_searched_at' => 'datetime', 'dismissed_at' => 'datetime'];

    /**
     * Enregistre une recherche sans résultat : terme en minuscules, espaces réduits.
     * Les termes trop courts (frappe en cours) sont ignorés.
     */
    public static function record(string $term): void
    {
        if (!$term = SearchTerm::normalize($term)) {
            return;
        }

        // Une seule requête atomique : deux recherches simultanées du même terme ne provoquent pas d'erreur d'unicité.
        $now = now();
        DB::table('search_misses')->upsert(
            [['term' => $term, 'count' => 1, 'last_searched_at' => $now, 'dismissed_at' => null, 'created_at' => $now, 'updated_at' => $now]],
            ['term'],
            ['count' => DB::raw('count + 1'), 'last_searched_at' => $now, 'dismissed_at' => null, 'updated_at' => $now],
        );
    }
}
