<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $resetToken;

    public function __construct($user, $resetToken)
    {
        $this->user = $user;
        $this->resetToken = $resetToken;
    }

    public function build()
    {
        $resetUrl = config('frontend.url') . '/auth/reset-password?' . http_build_query([
            'token' => $this->resetToken,
            'email' => $this->user->email,
        ]);

        return $this->markdown('emails.password-reset')
                    ->subject('Réinitialiser votre mot de passe')
                    ->with([
                        'user' => $this->user,
                        'resetUrl' => $resetUrl,
                        'expiresIn' => '1 heure',
                    ]);
    }
}
