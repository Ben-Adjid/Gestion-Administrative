<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session d'équipe : expiration après 15 minutes d'INACTIVITÉ (et non 15 minutes après la connexion).
 * Chaque requête authentifiée repousse l'échéance ; un poste laissé sans activité est déconnecté.
 */
class ExtendAdminSession
{
    public const IDLE_MINUTES = 15;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $token = $user instanceof Admin ? $user->currentAccessToken() : null;

        // Écriture limitée à une fois par minute au plus.
        if ($token instanceof PersonalAccessToken
            && $token->expires_at
            && $token->expires_at->isFuture()
            && $token->expires_at->lt(now()->addMinutes(self::IDLE_MINUTES - 1))) {
            $token->forceFill(['expires_at' => now()->addMinutes(self::IDLE_MINUTES)])->save();
        }

        return $response;
    }
}
