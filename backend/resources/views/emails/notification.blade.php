@component('mail::message')
# {{ $icon }} {{ $title }}

{{ $recipientName ?: 'Madame, Monsieur' }},

{{ $message }}

@component('mail::button', ['url' => config('app.url') . '/profile'])
Voir votre compte
@endcomponent

---

**À propos de cet email:** Vous recevez cet email car vous êtes inscrit sur DemarchesTchad.

[Gérer vos préférences]({{ config('app.url') . '/settings/notifications' }})
@endcomponent
