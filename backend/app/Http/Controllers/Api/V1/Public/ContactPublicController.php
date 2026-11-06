<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Signalement;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Formulaire « Nous contacter » : le message est enregistré (visible dans l'espace administrateur)
 * et l'équipe est prévenue par e-mail. L'adresse de l'équipe n'est jamais exposée au public.
 */
class ContactPublicController extends Controller
{
    private const MAX_PER_HOUR = 5;

    private const SUBJECTS = [
        'general' => 'Question générale',
        'error' => 'Erreur dans une fiche',
        'partnership' => 'Partenariat ou institution',
        'other' => 'Autre',
    ];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'in:' . implode(',', array_keys(self::SUBJECTS))],
            'message' => ['required', 'string', 'min:10', 'max:2500'],
            // Champ piège : invisible pour les humains. S'il est rempli, c'est un robot.
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        // Un robot reçoit une réponse de succès, sans rien enregistrer.
        if (filled($request->input('website'))) {
            return response()->json(['message' => 'Merci, votre message a bien été envoyé.'], 201);
        }

        $key = 'contact:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            return response()->json(['message' => 'Vous avez envoyé plusieurs messages. Réessayez dans une heure.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $label = self::SUBJECTS[$data['subject']];
        $message = Signalement::create([
            'type' => 'autre',
            'author_name' => $data['name'] ?? null,
            'author_email' => $data['email'],
            'message' => '[Contact : ' . $label . "]\n" . $data['message'],
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'status' => 'pending',
        ]);

        app(EmailService::class)->alertModerators(
            'Nouveau message de contact : ' . $label,
            "Un visiteur a écrit depuis le formulaire « Nous contacter ».\n\n"
                . Str::limit($data['message'], 300) . "\n\nRépondre : " . $data['email']
                . "\nTraiter dans l'espace administrateur : " . config('frontend.url') . '/admin/signalements',
            'notify_signalements',
        );

        return response()->json(['message' => 'Merci, votre message a bien été envoyé. Nous vous répondrons à l’adresse indiquée.', 'id' => $message->id], 201);
    }
}
