<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthUserController extends Controller
{
    public function __construct(private EmailService $emailService)
    {
    }

    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'language' => 'nullable|in:fr,en,ar',
            'accept_terms' => 'accepted',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'language' => $validated['language'] ?? 'fr',
        ]);

        $this->sendSafely(fn () => $this->emailService->sendWelcomeEmail($user), 'welcome', $user->id);

        return response()->json([
            'message' => 'Inscription réussie ! Bienvenue sur DemarchesTchad.',
            'data' => $this->profilePayload($user),
            'token' => $user->createToken('citizen-token')->plainTextToken,
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Même réponse pour un email inconnu, un mauvais mot de passe ou un compte désactivé.
        if (!$user || !Hash::check($credentials['password'], $user->password) || !$user->is_active || $user->is_banned) {
            return response()->json(['message' => __('messages.invalid_credentials')], 401);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'message' => 'Connexion réussie.',
            'data' => $this->profilePayload($user),
            'token' => $user->createToken('citizen-token')->plainTextToken,
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->profilePayload($request->user())]);
    }

    /**
     * PUT /api/v1/auth/profile
     * Seuls le nom, le téléphone et la langue sont modifiables par le citoyen.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'language' => 'sometimes|required|in:fr,en,ar',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'data' => $this->profilePayload($user->fresh()),
        ]);
    }

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->input('email'))->first();

        if ($user && $user->is_active && !$user->is_banned) {
            $plainToken = Str::random(64);
            $user->forceFill([
                'password_reset_token' => hash('sha256', $plainToken),
                'password_reset_expires_at' => now()->addHour(),
            ])->save();

            $this->sendSafely(fn () => $this->emailService->sendPasswordResetEmail($user, $plainToken), 'password_reset', $user->id);
        }

        return response()->json([
            'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.',
        ]);
    }

    /**
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::where('email', $validated['email'])->first();

        $tokenIsValid = $user
            && $user->password_reset_token
            && $user->password_reset_expires_at?->isFuture()
            && hash_equals($user->password_reset_token, hash('sha256', $validated['token']));

        if (!$tokenIsValid) {
            return response()->json(['message' => __('messages.reset_link_invalid')], 422);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
        ])->save();

        // Toute session ouverte avec l'ancien mot de passe est révoquée.
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès. Veuillez vous connecter.',
        ]);
    }

    /**
     * POST /api/v1/auth/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => __('messages.current_password_wrong'),
                'errors' => ['current_password' => [__('messages.current_password_wrong')]],
            ], 422);
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();

        // Les autres appareils sont déconnectés ; la session courante reste valide.
        $currentTokenId = $user->currentAccessToken()?->id;
        $user->tokens()->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))->delete();

        return response()->json(['message' => 'Mot de passe changé avec succès.']);
    }

    /**
     * DELETE /api/v1/auth/account
     * Suppression définitive du compte citoyen à sa demande (mot de passe exigé).
     * Les favoris et documents suivis sont supprimés par cascade.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $validated = $request->validate(['password' => 'required|string']);
        $user = $request->user();

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => __('messages.current_password_wrong'),
                'errors' => ['password' => [__('messages.current_password_wrong')]],
            ], 422);
        }

        $user->tokens()->delete();
        $user->forceDelete();

        return response()->json(['message' => __('messages.account_deleted')]);
    }

    private function profilePayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'language' => $user->language,
            'created_at' => $user->created_at?->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ];
    }

    private function sendSafely(callable $send, string $type, int $userId): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::error("Envoi de l'email {$type} impossible.", ['user_id' => $userId, 'exception' => $e]);
        }
    }
}
