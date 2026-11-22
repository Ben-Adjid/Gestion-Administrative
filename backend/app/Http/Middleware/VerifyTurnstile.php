<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie le jeton Cloudflare Turnstile envoyé par le site (en-tête X-Turnstile-Token).
 * Un jeton est à usage unique : Cloudflare le refuse s'il a déjà servi ou s'il a expiré.
 * Sans clé secrète configurée, la vérification est ignorée (développement).
 */
class VerifyTurnstile
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('turnstile.enabled')) {
            return $next($request);
        }

        $token = (string) ($request->header('X-Turnstile-Token') ?? $request->input('cf-turnstile-response', ''));
        if ($token === '' || strlen($token) > 2048) {
            return $this->refuse();
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('turnstile.timeout', 5))
                ->post(config('turnstile.verify_url'), [
                    'secret' => config('turnstile.secret'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (\Throwable $e) {
            // Cloudflare injoignable : on bloque plutôt que de laisser passer les robots.
            Log::warning('Turnstile injoignable', ['error' => $e->getMessage()]);
            return $this->refuse();
        }

        if (!$response->successful() || $response->json('success') !== true) {
            return $this->refuse();
        }

        return $next($request);
    }

    private function refuse(): Response
    {
        return response()->json([
            'message' => 'Vérification anti-robot non validée. Patientez un instant puis réessayez.',
            'code' => 'turnstile_failed',
        ], 422);
    }
}
