<?php

namespace App\Http\Controllers\Api\V1\Citizen;

use App\Http\Controllers\Controller;
use App\Models\UserDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/me/dashboard — tableau de bord personnel (module 7) :
 * mes documents, mes alertes actives, mes démarches sauvegardées. Données du seul citoyen connecté.
 */
class CitizenDashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $documents = $user->documents()->orderBy('expiration_date')->get();
        $payloads = $documents->map(fn (UserDocument $d) => UserDocumentController::payload($d));

        $language = $request->query('lang');
        $favorites = $user->favorites()
            ->whereHas('demarche', fn ($q) => $q->published())
            ->with('demarche')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'user' => ['name' => $user->name, 'email' => $user->email],
                'counters' => [
                    'documents' => $documents->count(),
                    'alerts' => $payloads->whereIn('state', ['urgent', 'expired'])->count(),
                    'expiring_soon' => $payloads->where('state', 'soon')->count(),
                    'favorites' => $user->favorites()->whereHas('demarche', fn ($q) => $q->published())->count(),
                ],
                'alerts' => $payloads->whereIn('state', ['urgent', 'expired', 'soon'])->values(),
                'recent_favorites' => $favorites->map(function ($f) use ($language) {
                    $content = $f->demarche->getTranslatedContent($language);

                    return [
                        'title' => $content['title'],
                        'slug' => $f->demarche->slug,
                        'cost' => $content['cost'],
                        'processing_time' => $content['processing_time'],
                        'saved_at' => $f->created_at?->toIso8601String(),
                    ];
                })->values(),
            ],
        ]);
    }
}
