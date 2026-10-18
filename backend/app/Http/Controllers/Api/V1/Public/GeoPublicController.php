<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\GeoUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Référentiel géographique public : provinces → départements → communes → localités.
 * Chaque unité indique son statut (officiel ou à vérifier) et sa source : rien n'est présenté comme confirmé sans source.
 */
class GeoPublicController extends Controller
{
    /** GET /api/v1/geo/tree : arbre complet des unités actives (provinces en tête). */
    public function tree(): JsonResponse
    {
        $units = GeoUnit::active()->orderBy('name')->get();
        $byParent = $units->groupBy(fn (GeoUnit $u) => $u->parent_id ?? 0);

        $build = function (GeoUnit $unit) use (&$build, $byParent) {
            return $this->present($unit) + [
                'children' => ($byParent[$unit->id] ?? collect())->map($build)->values()->all(),
            ];
        };

        return response()->json([
            'data' => ($byParent[0] ?? collect())->map($build)->values()->all(),
            'counts' => [
                'provinces' => $units->where('level', 'province')->count(),
                'departments' => $units->where('level', 'department')->count(),
                'communes' => $units->where('level', 'commune')->count(),
                'localities' => $units->where('level', 'locality')->count(),
                'official' => $units->where('status', 'official')->count(),
                'to_verify' => $units->where('status', 'to_verify')->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/geo/search?q=&level=&parent=slug : recherche par nom (sans accents) dans les unités actives.
     * Un résultat vide signifie « non répertorié » : le site propose alors un signalement.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        $level = in_array($request->query('level'), GeoUnit::LEVELS, true) ? $request->query('level') : null;

        $query = GeoUnit::active()->with('parent')->orderBy('name')->limit(50);
        if ($level) {
            $query->where('level', $level);
        }
        if ($parentSlug = $request->query('parent')) {
            $parent = GeoUnit::where('slug', (string) $parentSlug)->first();
            $query->where('parent_id', $parent?->id ?? 0);
        }
        if (mb_strlen($term) >= 2) {
            $query->where('name', 'like', '%' . $term . '%');
        }

        return response()->json([
            'data' => $query->get()->map(fn (GeoUnit $u) => $this->present($u) + ['path' => $u->path()])->values(),
        ]);
    }

    private function present(GeoUnit $unit): array
    {
        return [
            'id' => $unit->id,
            'level' => $unit->level,
            'name' => $unit->name,
            'name_ar' => $unit->name_ar,
            'slug' => $unit->slug,
            'status' => $unit->status,
            'source_label' => $unit->source_label,
            'source_url' => $unit->source_url,
            'verified_on' => $unit->verified_on?->toDateString(),
        ];
    }
}
