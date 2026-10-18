<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Terme recherché dans le catalogue, avec son nombre de recherches (terme seul, aucune donnée personnelle). */
class SearchTerm extends Model
{
    protected $fillable = ['term', 'count', 'last_results', 'last_searched_at'];

    protected $casts = ['count' => 'integer', 'last_results' => 'integer', 'last_searched_at' => 'datetime'];

    /** Terme en minuscules, espaces réduits ; null si trop court (frappe en cours). */
    public static function normalize(string $term): ?string
    {
        $term = Str::of($term)->lower()->squish()->substr(0, 100)->toString();

        return mb_strlen($term) < 3 ? null : $term;
    }

    /** Compte une recherche en une seule requête atomique (pas de doublon si deux visiteurs cherchent en même temps). */
    public static function record(string $term, int $results): void
    {
        if (!$term = static::normalize($term)) {
            return;
        }

        $now = now();
        DB::table('search_terms')->upsert(
            [['term' => $term, 'count' => 1, 'last_results' => $results, 'last_searched_at' => $now, 'created_at' => $now, 'updated_at' => $now]],
            ['term'],
            ['count' => DB::raw('count + 1'), 'last_results' => $results, 'last_searched_at' => $now, 'updated_at' => $now],
        );
    }
}
