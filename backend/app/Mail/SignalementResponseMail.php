<?php

namespace App\Mail;

use App\Models\Signalement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SignalementResponseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Signalement $signalement,
        public string $response
    ) {
    }

    public function build(): self
    {
        return $this->subject('Réponse à votre signalement — DemarchesTchad')
            ->markdown('emails.signalement-response')
            ->with([
                'signalement' => $this->signalement,
                'response' => $this->response,
            ]);
    }
}
