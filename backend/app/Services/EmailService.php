<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;
use App\Mail\PasswordResetMail;
use App\Mail\SignalementResponseMail;
use App\Mail\NotificationMail;
use App\Models\Admin;

class EmailService
{
    /**
     * Alerte immédiate des modérateurs (super admins et admins actifs) qui ont activé la préférence donnée.
     * Les administrateurs qui ont choisi « jamais » ne reçoivent rien. Les récapitulatifs quotidien et hebdomadaire
     * ne sont pas encore produits : ces comptes reçoivent l'alerte immédiate.
     *
     * @param  string  $preference  colonne booléenne de la table admins (notify_signalements, notify_community…)
     * @return int nombre de destinataires
     */
    public function alertModerators(string $subject, string $body, string $preference): int
    {
        $recipients = Admin::query()
            ->where('is_active', true)
            ->whereIn('role', ['super_admin', 'admin'])
            ->where($preference, true)
            ->where('notification_frequency', '!=', 'never')
            ->get();

        foreach ($recipients as $admin) {
            Mail::to($admin->email)->queue(new NotificationMail($admin, $subject, $body, 'admin'));
        }

        return $recipients->count();
    }

    /**
     * Envoyer email de bienvenue
     */
    public function sendWelcomeEmail($user, $password = null)
    {
        Mail::to($user->email)->queue(new WelcomeMail($user, $password));
        return true;
    }

    /**
     * Envoyer email réinitialisation mot de passe
     */
    public function sendPasswordResetEmail($user, $resetToken)
    {
        Mail::to($user->email)->queue(new PasswordResetMail($user, $resetToken));
        return true;
    }

    /**
     * Envoyer réponse à un signalement
     */
    public function sendSignalementResponse($signalement, $response)
    {
        Mail::to($signalement->author_email)->queue(new SignalementResponseMail($signalement, $response));
        return true;
    }

    /**
     * Envoyer notification utilisateur
     */
    public function sendNotification($user, $title, $message, $type = 'info')
    {
        Mail::to($user->email)->queue(new NotificationMail($user, $title, $message, $type));
        return true;
    }

    /**
     * Envoyer email d'alerte démarche
     */
    public function sendDemarcheAlert($user, $demarche)
    {
        $subject = "Rappel: {$demarche->title}";
        $body = "Vous aviez sauvegardé cette démarche: {$demarche->title}";

        Mail::to($user->email)->queue(new NotificationMail($user, $subject, $body, 'alert'));
        return true;
    }

    /**
     * Envoyer rapport admin
     */
    public function sendAdminReport($admin, $report)
    {
        $subject = $report['title'];
        $body = $report['content'];

        Mail::to($admin->email)->queue(new NotificationMail($admin, $subject, $body, 'admin'));
        return true;
    }

    /**
     * Envoyer en bulk (Newsletter)
     */
    public function sendBulkEmail($recipients, $subject, $body, $lang = 'fr')
    {
        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue(new NotificationMail($recipient, $subject, $body, 'newsletter', $lang));
        }
        return count($recipients);
    }
}
