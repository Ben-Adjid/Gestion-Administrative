<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MonthlyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, mixed> $report */
    public function __construct(public array $report)
    {
    }

    public function build()
    {
        $label = $this->report['month']->locale('fr')->translatedFormat('F Y');

        return $this->markdown('emails.monthly-report')
            ->subject("Rapport mensuel DemarchesTchad — {$label}")
            ->with([
                'report' => $this->report,
                'label' => $label,
                'adminUrl' => config('frontend.url') . '/admin/dashboard',
            ]);
    }
}
