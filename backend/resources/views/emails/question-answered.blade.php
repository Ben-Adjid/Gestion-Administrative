@component('mail::message')
# Réponse à votre question

Bonjour{{ $question->author_name ? ' ' . $question->author_name : '' }},

Vous avez posé cette question sur DemarchesTchad :

> {{ \Illuminate\Support\Str::limit($question->body, 500) }}

**Réponse de l'équipe :**

{{ $answer->body }}

@component('mail::button', ['url' => $url])
Voir la question en ligne
@endcomponent

Informations données à titre indicatif : vérifiez toujours auprès du service concerné.

L'équipe DemarchesTchad
@endcomponent
