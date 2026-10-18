@component('mail::message')
# Merci pour votre signalement

Bonjour{{ $signalement->author_name ? ' ' . $signalement->author_name : '' }},

Nous avons bien reçu votre signalement (n° {{ $signalement->id }}){{ $demarche ? ' concernant la fiche « ' . $demarche->title . ' »' : '' }}.

> {{ \Illuminate\Support\Str::limit($signalement->message, 500) }}

L'équipe va le vérifier et corriger la fiche si nécessaire. Si une réponse est utile, elle vous sera envoyée à cette adresse.

@component('mail::button', ['url' => $siteUrl])
Retourner sur DemarchesTchad
@endcomponent

Vous recevez cet email parce que vous avez indiqué votre adresse dans le formulaire de signalement.

L'équipe DemarchesTchad
@endcomponent
