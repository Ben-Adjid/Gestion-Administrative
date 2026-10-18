@component('mail::message')
# Réponse à votre signalement

Bonjour {{ $signalement->author_name ?: 'Madame, Monsieur' }},

Notre équipe a examiné votre signalement concernant
**{{ $signalement->demarche?->title ?: 'une démarche administrative' }}**.

{{ $response }}

Merci de contribuer à l’amélioration des informations disponibles sur DemarchesTchad.

L’équipe DemarchesTchad
@endcomponent
