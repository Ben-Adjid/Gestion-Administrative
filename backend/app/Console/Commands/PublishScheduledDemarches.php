<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Demarche;
use Illuminate\Console\Command;

/** Publie les fiches dont la date de publication programmée est atteinte (lancée chaque minute par le planificateur). */
class PublishScheduledDemarches extends Command
{
    protected $signature = 'demarches:publish-scheduled';

    protected $description = 'Publie les fiches dont la date de publication programmée est atteinte.';

    public function handle(): int
    {
        $due = Demarche::query()
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now())
            ->where('status', '!=', 'published')
            ->get();

        foreach ($due as $demarche) {
            $old = $demarche->status;
            $demarche->update(['status' => 'published', 'scheduled_publish_at' => null]);
            ActivityLog::logAction(
                null,
                'publish_demarche',
                "Publication programmée de la démarche « {$demarche->title} »",
                Demarche::class,
                $demarche->id,
                ['status' => $old],
                ['status' => 'published'],
            );
        }

        $this->info($due->count() . ' fiche(s) publiée(s).');

        return self::SUCCESS;
    }
}
