@component('mail::message')
# Fiches à vérifier

Ces fiches publiées n'ont pas été vérifiées depuis plus de {{ $months }} mois (ou jamais).
Les prix, pièces et adresses ont pu changer : vérifiez-les auprès des services concernés, puis cliquez sur « Marquer comme vérifiée » dans l'administration.

@foreach ($demarches as $demarche)
- [{{ $demarche->title }}]({{ $siteUrl }}/demarches/{{ $demarche->slug }}) — {{ $demarche->last_verified_at ? 'vérifiée le ' . $demarche->last_verified_at->format('d/m/Y') : 'jamais vérifiée' }}
@endforeach

@component('mail::button', ['url' => $adminUrl])
Ouvrir les démarches
@endcomponent

Vous recevez ce rappel car l'option « mises à jour des démarches » est activée sur votre compte d'équipe.

L'équipe DemarchesTchad
@endcomponent
