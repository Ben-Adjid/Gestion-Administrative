<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCitizen
{
    /**
     * Réserve la route aux comptes citoyens actifs : un jeton administrateur est refusé.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof User || !$user->is_active || $user->is_banned) {
            return response()->json([
                'message' => __('messages.citizen_only'),
            ], 403);
        }

        return $next($request);
    }
}
