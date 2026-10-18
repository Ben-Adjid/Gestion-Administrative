<?php

namespace App\Mail;

use App\Models\Signalement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Accusé de réception envoyé au citoyen qui a laissé son email (cahier des charges, module 3). */
class SignalementReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Signalement $signalement)
    {
    }

    public function build()
    {
        return $this->subject('Nous avons bien reçu votre signalement — DemarchesTchad')
            ->markdown('emails.signalement-received')
            ->with([
                'signalement' => $this->signalement,
                'demarche' => $this->signalement->demarche,
                'siteUrl' => config('frontend.url'),
            ]);
    }
}
