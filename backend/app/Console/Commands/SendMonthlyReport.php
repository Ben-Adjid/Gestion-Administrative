<?php

namespace App\Console\Commands;

use App\Mail\MonthlyReportMail;
use App\Models\Admin;
use App\Services\MonthlyReport;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonthlyReport extends Command
{
    protected $signature = 'report:monthly {--month= : Mois au format AAAA-MM (par défaut : le mois précédent)} {--dry-run : Affiche le rapport sans l\'envoyer}';

    protected $description = 'Envoie le rapport mensuel (fréquentation, signalements, contenu) aux administrateurs abonnés';

    public function handle(MonthlyReport $builder): int
    {
        $option = $this->option('month');
        if ($option && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $option)) {
            $this->error('Format attendu : AAAA-MM (ex. 2026-09).');
            return self::INVALID;
        }

        $month = $option
            ? CarbonImmutable::createFromFormat('Y-m-d', "{$option}-01")
            : CarbonImmutable::now()->subMonthNoOverflow();
        $report = $builder->build($month);

        $this->table(['Indicateur', 'Valeur'], [
            ['Pages vues', $report['page_views']],
            ['Visiteurs uniques', $report['unique_visitors']],
            ['Signalements reçus', $report['signalements_received']],
            ['Signalements traités', $report['signalements_resolved']],
            ['Signalements en attente', $report['signalements_pending']],
            ['Nouveaux comptes citoyens', $report['new_citizens']],
            ['Fiches modifiées', $report['demarches_updated']],
            ['Fiches jamais vérifiées', $report['demarches_never_verified']],
        ]);

        // Destinataires : administrateurs actifs ayant activé l'option « rapports ».
        $recipients = Admin::where('is_active', true)
            ->whereIn('role', ['super_admin', 'admin'])
            ->where('notify_admin_reports', true)
            ->get();

        if ($this->option('dry-run')) {
            $this->info("Simulation : {$recipients->count()} destinataire(s), aucun email envoyé.");
            return self::SUCCESS;
        }

        foreach ($recipients as $admin) {
            Mail::to($admin->email)->queue(new MonthlyReportMail($report));
        }

        $this->info("Rapport mis en file d'envoi pour {$recipients->count()} destinataire(s).");
        return self::SUCCESS;
    }
}
