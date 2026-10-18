<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Demarche;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Démarches sauvegardées par le citoyen connecté. Toutes les requêtes passent par
 * $request->user()->favorites() : un citoyen ne peut jamais lire ni modifier les favoris d'un autre.
 */
class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()
            ->whereHas('demarche', fn ($q) => $q->published())
            ->with('demarche.categorie')
            ->latest()
            ->paginate(min(max((int) $request->query('per_page', 20), 1), 50));

        return response()->json([
            'data' => $favorites->getCollection()->map(fn (Favorite $f) => $this->payload($f))->values(),
            'meta' => [
                'total' => $favorites->total(),
                'per_page' => $favorites->perPage(),
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
            ],
        ]);
    }

    /** POST /favorites/{demarche} — idempotent : sauvegarder deux fois ne crée pas de doublon. */
    public function store(Request $request, int $demarche): JsonResponse
    {
        $target = Demarche::published()->findOrFail($demarche);

        $favorite = $request->user()->favorites()->firstOrCreate(['demarche_id' => $target->id]);

        return response()->json([
            'message' => 'Démarche ajoutée à vos favoris.',
            'is_favorite' => true,
            'data' => $this->payload($favorite->load('demarche.categorie')),
        ], $favorite->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, int $demarche): JsonResponse
    {
        $request->user()->favorites()->where('demarche_id', $demarche)->firstOrFail()->delete();

        return response()->json(['message' => 'Démarche retirée de vos favoris.', 'is_favorite' => false]);
    }

    public function status(Request $request, int $demarche): JsonResponse
    {
        return response()->json([
            'is_favorite' => $request->user()->favorites()->where('demarche_id', $demarche)->exists(),
        ]);
    }

    public function updateNote(Request $request, int $demarche): JsonResponse
    {
        $validated = $request->validate(['note' => 'nullable|string|max:1000']);

        $favorite = $request->user()->favorites()->where('demarche_id', $demarche)->firstOrFail();
        $favorite->update(['note' => $validated['note'] ?? null]);

        return response()->json([
            'message' => 'Note enregistrée.',
            'data' => $this->payload($favorite->load('demarche.categorie')),
        ]);
    }

    private function payload(Favorite $favorite): array
    {
        $d = $favorite->demarche;
        $language = request()->query('lang');
        $content = $d->getTranslatedContent($language);

        return [
            'id' => $favorite->id,
            'note' => $favorite->note,
            'saved_at' => $favorite->created_at?->toIso8601String(),
            'demarche' => [
                'id' => $d->id,
                'title' => $content['title'],
                'slug' => $d->slug,
                'summary' => $content['summary'],
                'cost' => $content['cost'],
                'processing_time' => $content['processing_time'],
                'categorie' => $d->categorie?->localizedName($language),
            ],
        ];
    }
}
