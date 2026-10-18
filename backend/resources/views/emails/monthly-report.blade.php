@component('mail::message')
# Rapport mensuel — {{ $label }}

## Fréquentation
- Pages vues : **{{ number_format($report['page_views'], 0, ',', ' ') }}**
- Visiteurs uniques : **{{ number_format($report['unique_visitors'], 0, ',', ' ') }}**

@if (count($report['top_demarches']))
**Fiches les plus consultées**

@foreach ($report['top_demarches'] as $index => $item)
{{ $index + 1 }}. {{ $item['title'] }} — {{ $item['views'] }} {{ $item['views'] > 1 ? 'vues' : 'vue' }}
@endforeach
@endif

## Signalements
- Reçus ce mois : **{{ $report['signalements_received'] }}**
- Traités ce mois : **{{ $report['signalements_resolved'] }}**
- Encore en attente : **{{ $report['signalements_pending'] }}**

## Contenu et comptes
- Fiches publiées : **{{ $report['demarches_published'] }}**
- Fiches modifiées ce mois : **{{ $report['demarches_updated'] }}**
- Fiches publiées jamais vérifiées : **{{ $report['demarches_never_verified'] }}**
- Nouveaux comptes citoyens : **{{ $report['new_citizens'] }}**

@component('mail::button', ['url' => $adminUrl])
Ouvrir le tableau de bord
@endcomponent

Vous recevez ce rapport car l'option « rapports » est activée sur votre compte d'équipe.

L'équipe DemarchesTchad
@endcomponent
