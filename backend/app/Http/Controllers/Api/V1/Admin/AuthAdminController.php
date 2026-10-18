<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ExtendAdminSession;
use App\Http\Requests\Api\V1\Admin\AdminLoginRequest;
use App\Models\Admin;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthAdminController extends Controller
{
    /**
     * POST /api/v1/admin/auth/login
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $admin = Admin::where('email', $request->email)->first();

        // Vérifier si le compte est verrouillé
        if ($admin && $admin->locked_until && now()->isBefore($admin->locked_until)) {
            throw ValidationException::withMessages([
                'email' => [__('messages.account_locked')],
            ]);
        }

        // Réinitialiser les tentatives échouées si le compte n'était plus verrouillé
        if ($admin && $admin->locked_until && now()->isAfter($admin->locked_until)) {
            $admin->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);
        }

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            // Incrémenter les tentatives échouées
            if ($admin) {
                $attempts = $admin->failed_login_attempts + 1;
                $lockUntil = $attempts >= 10 ? now()->addHours(24) : null;

                $admin->update([
                    'failed_login_attempts' => $attempts,
                    'locked_until' => $lockUntil,
                ]);

                if ($lockUntil) {
                    ActivityLog::logAction(
                        $admin->id,
                        'account_locked',
                        "Compte verrouillé après 10 tentatives de connexion échouées",
                        Admin::class,
                        $admin->id
                    );
                }
            }

            // Message générique pour éviter l'énumération d'utilisateurs
            throw ValidationException::withMessages([
                'email' => [__('messages.invalid_credentials')],
            ]);
        }

        if (!$admin->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('messages.invalid_credentials')],
            ]);
        }

        // Réinitialiser les tentatives échouées après connexion réussie
        $admin->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Création du Token Sanctum avec expiration
        $token = $admin->createToken('admin-token', [$admin->role], now()->addMinutes(ExtendAdminSession::IDLE_MINUTES))->plainTextToken;

        // Audit Log
        ActivityLog::logAction(
            $admin->id,
            'admin_login',
            "Connexion réussie de l'administrateur {$admin->email}",
            Admin::class,
            $admin->id
        );

        return response()->json([
            'message' => 'Connexion réussie',
            'token' => $token,
            'expires_at' => now()->addMinutes(ExtendAdminSession::IDLE_MINUTES)->toIso8601String(),
            'admin' => $this->adminPayload($admin),
        ]);
    }

    /**
     * GET /api/v1/admin/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['admin' => $this->adminPayload($request->user())]);
    }

    /**
     * POST /api/v1/admin/auth/change-password
     * Les autres sessions du compte sont révoquées ; la session courante reste ouverte.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'confirmed', 'different:current_password', 'min:12', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/'],
        ]);

        /** @var Admin $admin */
        $admin = $request->user();

        if (!Hash::check($validated['current_password'], $admin->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.password')],
            ]);
        }

        $admin->forceFill(['password' => Hash::make($validated['password'])])->save();

        $currentTokenId = $admin->currentAccessToken()?->id;
        $admin->tokens()->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))->delete();

        ActivityLog::logAction($admin->id, 'admin_password_changed', "Changement de mot de passe de {$admin->email}", Admin::class, $admin->id);

        return response()->json(['message' => 'OK']);
    }

    private function adminPayload(Admin $admin): array
    {
        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
            'capabilities' => $admin->capabilities(),
        ];
    }

    /**
     * POST /api/v1/admin/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $admin = $request->user();

        // Révocation du token courant
        $request->user()->currentAccessToken()->delete();

        ActivityLog::logAction(
            $admin->id,
            'admin_logout',
            "Déconnexion de l'administrateur {$admin->email}",
            Admin::class,
            $admin->id
        );

        return response()->json([
            'message' => 'Déconnexion effectuée avec succès.',
        ]);
    }
}
