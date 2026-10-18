<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $title;
    public $message;
    public $type;
    public $lang;
    public $recipientName;

    public function __construct($user, $title, $message, $type = 'info', $lang = 'fr')
    {
        $this->user = $user;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->lang = $lang;
        $this->recipientName = is_object($user) ? ($user->name ?? '') : '';
    }

    public function build()
    {
        $icon = $this->getIcon();

        return $this->markdown('emails.notification')
                    ->subject($this->title)
                    ->with([
                        'user' => $this->user,
                        'title' => $this->title,
                        'message' => $this->message,
                        'type' => $this->type,
                        'icon' => $icon,
                        'lang' => $this->lang,
                        'recipientName' => $this->recipientName,
                    ]);
    }

    private function getIcon()
    {
        $icons = [
            'info' => 'ℹ️',
            'alert' => '🔔',
            'warning' => '⚠️',
            'error' => '❌',
            'success' => '✅',
            'admin' => '👨‍💼',
            'newsletter' => '📰',
        ];

        return $icons[$this->type] ?? '📬';
    }
}
