<?php

namespace App\Mail;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Prévient l'auteur d'une question (s'il a laissé son email) que l'équipe y a répondu. */
class QuestionAnsweredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Question $question, public Answer $answer)
    {
    }

    public function build()
    {
        return $this->subject('Réponse à votre question — DemarchesTchad')
            ->markdown('emails.question-answered')
            ->with([
                'question' => $this->question,
                'answer' => $this->answer,
                'url' => config('frontend.url') . '/questions#question-' . $this->question->id,
            ]);
    }
}
