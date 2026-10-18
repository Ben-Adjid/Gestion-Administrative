<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Protection des formulaires publics (avis, questions, réponses) :
 * - limite horaire par adresse IP, qui ne compte que les envois réellement enregistrés ;
 * - champ piège « website » invisible pour les humains, rempli par les robots ;
 * - adresse IP jamais stockée en clair (empreinte HMAC).
 */
trait LimitsPublicSubmissions
{
    protected function submissionKey(Request $request, string $name): string
    {
        return "public-submission:{$name}:" . $request->ip();
    }

    protected function submissionLimitResponse(Request $request, string $name, int $maxPerHour): ?JsonResponse
    {
        $key = $this->submissionKey($request, $name);
        if (!RateLimiter::tooManyAttempts($key, $maxPerHour)) {
            return null;
        }

        $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

        return response()->json([
            'message' => trans_choice('messages.submission_limit', $minutes, ['minutes' => $minutes]),
        ], 429);
    }

    protected function recordSubmission(Request $request, string $name): void
    {
        RateLimiter::hit($this->submissionKey($request, $name), 3600);
    }

    /** Vrai si le champ piège a été rempli : on répond « reçu » sans rien enregistrer. */
    protected function isBot(Request $request): bool
    {
        return filled($request->input('website'));
    }

    protected function ipHash(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }
}
