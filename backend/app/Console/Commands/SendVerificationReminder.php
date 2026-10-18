<?php

namespace App\Console\Commands;

use App\Mail\VerificationReminderMail;
use App\Models\Admin;
use App\Models\Demarche;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendVerificationReminder extends Command
{
    public const STALE_MONTHS = 6;

    protected $signature = 'demarches:verification-reminder {--dry-run : Affiche la liste sans envoyer d\'email}';

    protected $description = 'Rappelle aux administrateurs les fiches publiées non vérifiées depuis 6 mois';

    public function handle(): int
    {
        $stale = Demarche::published()
            ->where(fn ($q) => $q->whereNull('last_verified_at')->orWhere('last_verified_at', '<', now()->subMonths(self::STALE_MONTHS)))
            ->orderByRaw('last_verified_at IS NOT NULL')->orderBy('last_verified_at')
            ->get(['id', 'title', 'slug', 'last_verified_at']);

        $this->info("{$stale->count()} fiche(s) à vérifier.");
        foreach ($stale as $demarche) {
            $this->line(' - ' . $demarche->title . ' (' . ($demarche->last_verified_at?->format('d/m/Y') ?? 'jamais vérifiée') . ')');
        }

        if ($stale->isEmpty()) {
            return self::SUCCESS;
        }

        // Destinataires : administrateurs actifs abonnés aux alertes de mise à jour des fiches.
        $recipients = Admin::where('is_active', true)
            ->whereIn('role', ['super_admin', 'admin'])
            ->where('notify_demarche_updates', true)
            ->get();

        if ($this->option('dry-run')) {
            $this->info("Simulation : {$recipients->count()} destinataire(s), aucun email envoyé.");
            return self::SUCCESS;
        }

        foreach ($recipients as $admin) {
            Mail::to($admin->email)->queue(new VerificationReminderMail($stale->all()));
        }
        $this->info("Rappel mis en file d'envoi pour {$recipients->count()} destinataire(s).");

        return self::SUCCESS;
    }
}
