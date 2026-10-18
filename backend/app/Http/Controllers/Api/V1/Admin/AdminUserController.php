<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    /**
     * GET /api/v1/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        $users = Admin::orderBy('created_at', 'desc')->get()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'is_active' => $u->is_active,
            'last_login_at' => $u->last_login_at?->toIso8601String(),
            'last_login_ip' => $u->last_login_ip,
            'is_locked' => $u->locked_until !== null && $u->locked_until->isFuture(),
            'created_at' => $u->created_at->toIso8601String(),
        ]);

        return response()->json(['data' => $users]);
    }

    /**
     * POST /api/v1/admin/users
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:12|regex:/[A-Z]/|regex:/[0-9]/|regex:/[!@#$%^&*]/i',
            'role' => 'required|in:super_admin,admin,editor',
        ], [
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.regex' => 'Le mot de passe doit contenir au moins une majuscule, un chiffre et un caractère spécial.',
            'password.min' => 'Le mot de passe doit contenir au moins 12 caractères.',
        ]);

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        ActivityLog::logAction(
            $request->user()->id,
            'user_created',
            "Création du compte administrateur {$admin->email} avec le rôle {$admin->role}",
            Admin::class,
            $admin->id
        );

        return response()->json([
            'data' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'is_active' => $admin->is_active,
            ],
        ], 201);
    }

    /**
     * PUT /api/v1/admin/users/{admin}
     */
    public function update(Request $request, Admin $admin): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:admins,email,' . $admin->id,
            'role' => 'sometimes|required|in:super_admin,admin,editor',
            'is_active' => 'sometimes|required|boolean',
        ]);

        $losesSuperAdmin = $admin->role === 'super_admin' && (
            (isset($validated['role']) && $validated['role'] !== 'super_admin')
            || (array_key_exists('is_active', $validated) && !$validated['is_active'])
        );

        if ($admin->id === $request->user()->id && (isset($validated['role']) && $validated['role'] !== $admin->role || (array_key_exists('is_active', $validated) && !$validated['is_active']))) {
            throw ValidationException::withMessages([
                'role' => [__('messages.self_role')],
            ]);
        }
        if ($losesSuperAdmin && $this->activeSuperAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'role' => [__('messages.last_super_admin')],
            ]);
        }

        $old = $admin->only(['name', 'email', 'role', 'is_active']);
        $admin->update($validated);

        // Un changement de rôle ou une désactivation invalide immédiatement les sessions ouvertes.
        if ($old['role'] !== $admin->role || ($old['is_active'] && !$admin->is_active)) {
            $admin->tokens()->delete();
        }

        ActivityLog::logAction(
            $request->user()->id,
            $old['role'] !== $admin->role ? 'user_role_updated' : 'user_updated',
            "Mise à jour du compte administrateur {$admin->email}",
            Admin::class,
            $admin->id,
            $old,
            $admin->only(['name', 'email', 'role', 'is_active'])
        );

        return response()->json(['data' => $admin->only(['id', 'name', 'email', 'role', 'is_active'])]);
    }

    /**
     * DELETE /api/v1/admin/users/{admin}
     */
    public function destroy(Request $request, Admin $admin): JsonResponse
    {
        if ($admin->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'email' => [__('messages.self_delete')],
            ]);
        }
        if ($admin->role === 'super_admin' && $admin->is_active && $this->activeSuperAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'email' => [__('messages.last_super_admin')],
            ]);
        }

        $admin->tokens()->delete();
        $admin->delete();

        ActivityLog::logAction(
            $request->user()->id,
            'user_deleted',
            "Compte administrateur {$admin->email} supprimé",
            Admin::class,
            $admin->id
        );

        return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
    }

    /**
     * POST /api/v1/admin/users/{admin}/reset-password
     */
    public function resetPassword(Request $request, Admin $admin): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string|min:12|regex:/[A-Z]/|regex:/[0-9]/|regex:/[!@#$%^&*]/i',
        ]);

        $admin->update(['password' => Hash::make($validated['password'])]);
        $admin->tokens()->delete();

        ActivityLog::logAction(
            $request->user()->id,
            'user_password_reset',
            "Mot de passe réinitialisé pour {$admin->email}",
            Admin::class,
            $admin->id
        );

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }

    /**
     * POST /api/v1/admin/users/{admin}/unlock
     */
    public function unlock(Request $request, Admin $admin): JsonResponse
    {
        $admin->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        ActivityLog::logAction(
            $request->user()->id,
            'user_unlocked',
            "Compte {$admin->email} déverrouillé",
            Admin::class,
            $admin->id
        );

        return response()->json(['message' => 'Compte déverrouillé.']);
    }

    private function activeSuperAdminCount(): int
    {
        return Admin::where('role', 'super_admin')->where('is_active', true)->count();
    }
}
