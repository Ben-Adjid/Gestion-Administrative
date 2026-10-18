<?php

namespace App\Mail;

use App\Console\Commands\SendVerificationReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Liste des fiches publiées à revérifier (non vérifiées depuis 6 mois ou jamais). */
class VerificationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<int, \App\Models\Demarche> $demarches */
    public function __construct(public array $demarches)
    {
    }

    public function build()
    {
        $count = count($this->demarches);

        return $this->subject($count . ($count > 1 ? ' fiches à vérifier' : ' fiche à vérifier') . ' — DemarchesTchad')
            ->markdown('emails.verification-reminder')
            ->with([
                'demarches' => $this->demarches,
                'months' => SendVerificationReminder::STALE_MONTHS,
                'adminUrl' => config('frontend.url') . '/admin/demarches',
                'siteUrl' => config('frontend.url'),
            ]);
    }
}
