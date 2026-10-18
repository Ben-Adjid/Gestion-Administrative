<?php

namespace App\Console\Commands;

use App\Models\UserDocument;
use App\Services\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDocumentReminders extends Command
{
    protected $signature = 'documents:send-reminders';

    protected $description = "Envoie les rappels d'expiration des documents citoyens (6 mois, 3 mois, 1 mois, 1 semaine)";

    public function handle(EmailService $emailService): int
    {
        $sent = 0;

        UserDocument::with('user')
            ->expiringWithin(max(UserDocument::REMINDER_DAYS))
            ->whereHas('user', fn ($q) => $q->where('is_active', true)->where('is_banned', false))
            ->chunkById(200, function ($documents) use ($emailService, &$sent) {
                foreach ($documents as $document) {
                    $threshold = $document->dueReminderThreshold();
                    if ($threshold === null) {
                        continue;
                    }

                    $days = $document->daysUntilExpiration();
                    $name = $document->displayName();

                    try {
                        $emailService->sendNotification(
                            $document->user,
                            "Rappel : votre {$name} expire bientôt",
                            "Votre {$name} expire le {$document->expiration_date->format('d/m/Y')} (dans {$days} " . ($days > 1 ? 'jours' : 'jour') . '). '
                                . 'Pensez à lancer le renouvellement dès maintenant : consultez la fiche démarche correspondante sur DemarchesTchad.',
                            'alert'
                        );
                    } catch (\Throwable $e) {
                        Log::error('Rappel de document non envoyé.', ['document_id' => $document->id, 'exception' => $e]);
                        continue;
                    }

                    $document->forceFill(['last_reminder_days' => $threshold, 'notified_at' => now()])->save();
                    $sent++;
                }
            });

        $this->info("{$sent} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
