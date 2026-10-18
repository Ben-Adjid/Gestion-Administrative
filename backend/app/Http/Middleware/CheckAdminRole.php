<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Autorise uniquement un compte administrateur actif possédant l'un des rôles indiqués.
     * Un jeton citoyen (App\Models\User) est toujours refusé, même s'il est valide.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $admin = $request->user();

        if (!$admin instanceof Admin || !$admin->is_active || !in_array($admin->role, $roles, true)) {
            return response()->json([
                'message' => __('messages.forbidden'),
            ], 403);
        }

        return $next($request);
    }
}
