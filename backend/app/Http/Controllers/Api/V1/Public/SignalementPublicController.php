<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSignalementRequest;
use App\Models\Signalement;
use App\Http\Resources\Api\V1\SignalementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use App\Mail\SignalementReceivedMail;

class SignalementPublicController extends Controller
{
    /** Signalements enregistrés autorisés par IP et par heure. */
    private const MAX_PER_HOUR = 3;

    /**
     * POST /api/v1/signalements
     * Seuls les signalements réellement enregistrés comptent dans la limite horaire :
     * un citoyen qui corrige une erreur de saisie n'est pas bloqué.
     */
    public function store(StoreSignalementRequest $request): JsonResponse
    {
        $key = 'signalements:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return response()->json([
                'message' => trans_choice('messages.signalement_limit', $minutes, ['minutes' => $minutes]),
            ], 429);
        }

        $validated = $request->validated();

        $signalement = Signalement::create([
            'demarche_id' => $validated['demarche_id'] ?? null,
            'type' => $validated['type'],
            'author_name' => $validated['author_name'] ?? null,
            'author_email' => $validated['author_email'] ?? null,
            'author_phone' => $validated['author_phone'] ?? null,
            'message' => $validated['message'],
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'status' => 'pending',
        ]);

        RateLimiter::hit($key, 3600);

        // Accusé de réception automatique si le citoyen a laissé son email.
        if (filled($signalement->author_email)) {
            Mail::to($signalement->author_email)->queue(new SignalementReceivedMail($signalement->load('demarche')));
        }

        return response()->json([
            'message' => __('messages.signalement_sent'),
            'data' => new SignalementResource($signalement),
        ], 201);
    }
}
