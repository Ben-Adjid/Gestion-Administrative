<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\EmailService;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Test d'envoi email
     * POST /api/v1/email/test
     */
    public function testEmail(Request $request)
    {
        $admin = auth('sanctum')->user();

        $this->emailService->sendNotification(
            $admin,
            'Test Email',
            'Ceci est un email de test pour vérifier que votre compte SMTP fonctionne correctement.',
            'success'
        );

        return response()->json([
            'message' => 'Email de test mis en file d’attente.',
            'email' => $admin->email,
        ]);
    }

    /**
     * Préférences de notification
     * GET /api/v1/email/preferences
     */
    public function getPreferences(Request $request)
    {
        $admin = auth('sanctum')->user();

        $preferences = [
            'email' => $admin->email,
            'notifications' => [
                'signalements' => $admin->notify_signalements ?? true,
                'demarche_updates' => $admin->notify_demarche_updates ?? true,
                'admin_reports' => $admin->notify_admin_reports ?? true,
                'newsletter' => $admin->notify_newsletter ?? false,
            ],
            'frequency' => $admin->notification_frequency ?? 'immediate',
        ];

        return response()->json($preferences);
    }

    /**
     * Mettre à jour préférences
     * PUT /api/v1/email/preferences
     */
    public function updatePreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notifications.signalements' => 'boolean',
            'notifications.demarche_updates' => 'boolean',
            'notifications.admin_reports' => 'boolean',
            'notifications.newsletter' => 'boolean',
            'frequency' => 'in:immediate,daily,weekly,never',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = auth('sanctum')->user();

        $admin->update([
            'notify_signalements' => $request->boolean('notifications.signalements', true),
            'notify_demarche_updates' => $request->boolean('notifications.demarche_updates', true),
            'notify_admin_reports' => $request->boolean('notifications.admin_reports', true),
            'notify_newsletter' => $request->boolean('notifications.newsletter', false),
            'notification_frequency' => $request->input('frequency', 'immediate'),
        ]);

        return $this->getPreferences($request)->setStatusCode(200);
    }

    /**
     * Envoyer email personnalisé (Admin only)
     * POST /api/v1/email/send
     */
    public function sendCustomEmail(Request $request)
    {
        $admin = auth('sanctum')->user();

        if ($admin->role !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'recipient_email' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'in:info,alert,warning,success',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = (object) ['email' => $request->input('recipient_email'), 'name' => 'Utilisateur'];

            $this->emailService->sendNotification(
                $user,
                $request->input('subject'),
                $request->input('body'),
                $request->input('type', 'info')
            );

            return response()->json([
                'message' => 'Email envoyé avec succès',
                'recipient' => $request->input('recipient_email'),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Envoi d'un email personnalisé impossible.", ['exception' => $e]);

            return response()->json(['message' => "L'email n'a pas pu être envoyé."], 500);
        }
    }
}
