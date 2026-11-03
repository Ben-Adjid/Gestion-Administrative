<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Demarche;
use Illuminate\Http\JsonResponse;

/**
 * Qualité du catalogue : ce qui manque à chaque fiche pour être fiable (source, vérification, règles).
 * Lecture seule : les corrections passent par la fiche elle-même, journalisée.
 */
class AdminQualityController extends Controller
{
    /** Au-delà, une vérification est considérée comme périmée (cahier des charges, chapitre 15). */
    private const STALE_AFTER_MONTHS = 6;
    /** Minimum de règles de gestion annoncé par le cahier des charges v2.0. */
    private const MIN_RULES = 4;

    /**
     * Liste de contrôle de chaque brouillon : ce qui manque avant publication.
     * Une fiche n'est « prête » que si tous les critères sont remplis.
     */
    public function draftChecklist(): JsonResponse
    {
        $drafts = Demarche::where('status', 'draft')->with('categorie')->orderBy('title')->get();

        $rows = $drafts->map(function (Demarche $d) {
            $checks = [
                'categorie' => $d->categorie_id !== null,
                'institution' => filled($d->institution) && $d->institution !== 'À renseigner',
                'cout' => filled($d->cost) && $d->cost !== 'À renseigner',
                'delai' => filled($d->processing_time) && $d->processing_time !== 'À renseigner',
                'pieces' => ! empty($d->required_documents),
                'etapes' => ! empty($d->procedure_steps),
                'regles' => count($d->conditions ?? []) >= self::MIN_RULES,
                'source_officielle' => filled($d->official_source_url),
                'verifiee' => $d->last_verified_at !== null,
            ];

            return [
                'id' => $d->id,
                'title' => $d->title,
                'checks' => $checks,
                'missing' => array_keys(array_filter($checks, fn ($ok) => ! $ok)),
                'ready' => ! in_array(false, $checks, true),
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'ready' => $rows->where('ready', true)->count(),
            'total' => $rows->count(),
        ]);
    }

    public function index(): JsonResponse
    {
        $published = Demarche::where('status', 'published')->get();
        $drafts = Demarche::where('status', 'draft')->get();
        $stale = now()->subMonths(self::STALE_AFTER_MONTHS);

        $issuesOf = fn (Demarche $d) => array_values(array_filter([
            blank($d->official_source_url) ? 'sans_source' : null,
            $d->last_verified_at === null ? 'non_verifiee' : ($d->last_verified_at->lt($stale) ? 'verification_perimee' : null),
            count($d->conditions ?? []) < self::MIN_RULES ? 'moins_de_4_regles' : null,
        ]));

        $items = $published->map(fn (Demarche $d) => [
            'id' => $d->id,
            'slug' => $d->slug,
            'title' => $d->title,
            'issues' => $issuesOf($d),
        ])->filter(fn (array $row) => $row['issues'] !== [])->values();

        return response()->json([
            'data' => [
                'published' => $published->count(),
                'published_without_source' => $published->filter(fn ($d) => blank($d->official_source_url))->count(),
                'published_never_verified' => $published->filter(fn ($d) => $d->last_verified_at === null)->count(),
                'published_stale_verification' => $published->filter(fn ($d) => $d->last_verified_at !== null && $d->last_verified_at->lt($stale))->count(),
                'published_under_min_rules' => $published->filter(fn ($d) => count($d->conditions ?? []) < self::MIN_RULES)->count(),
                'drafts' => $drafts->count(),
                'drafts_ready_to_publish' => $drafts->filter(fn ($d) => $issuesOf($d) === [])->count(),
                'min_rules' => self::MIN_RULES,
                'stale_after_months' => self::STALE_AFTER_MONTHS,
            ],
            'items' => $items,
        ]);
    }
}
