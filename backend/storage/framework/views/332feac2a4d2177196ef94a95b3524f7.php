<?php $__env->startComponent('mail::message'); ?>
# Rapport mensuel — <?php echo new \Illuminate\Support\EncodedHtmlString($label); ?>


## Fréquentation
- Pages vues : **<?php echo new \Illuminate\Support\EncodedHtmlString(number_format($report['page_views'], 0, ',', ' ')); ?>**
- Visiteurs uniques : **<?php echo new \Illuminate\Support\EncodedHtmlString(number_format($report['unique_visitors'], 0, ',', ' ')); ?>**

<?php if(count($report['top_demarches'])): ?>
**Fiches les plus consultées**

<?php $__currentLoopData = $report['top_demarches']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php echo new \Illuminate\Support\EncodedHtmlString($index + 1); ?>. <?php echo new \Illuminate\Support\EncodedHtmlString($item['title']); ?> — <?php echo new \Illuminate\Support\EncodedHtmlString($item['views']); ?> <?php echo new \Illuminate\Support\EncodedHtmlString($item['views'] > 1 ? 'vues' : 'vue'); ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>

## Signalements
- Reçus ce mois : **<?php echo new \Illuminate\Support\EncodedHtmlString($report['signalements_received']); ?>**
- Traités ce mois : **<?php echo new \Illuminate\Support\EncodedHtmlString($report['signalements_resolved']); ?>**
- Encore en attente : **<?php echo new \Illuminate\Support\EncodedHtmlString($report['signalements_pending']); ?>**

## Contenu et comptes
- Fiches publiées : **<?php echo new \Illuminate\Support\EncodedHtmlString($report['demarches_published']); ?>**
- Fiches modifiées ce mois : **<?php echo new \Illuminate\Support\EncodedHtmlString($report['demarches_updated']); ?>**
- Fiches publiées jamais vérifiées : **<?php echo new \Illuminate\Support\EncodedHtmlString($report['demarches_never_verified']); ?>**
- Nouveaux comptes citoyens : **<?php echo new \Illuminate\Support\EncodedHtmlString($report['new_citizens']); ?>**

<?php $__env->startComponent('mail::button', ['url' => $adminUrl]); ?>
Ouvrir le tableau de bord
<?php echo $__env->renderComponent(); ?>

Vous recevez ce rapport car l'option « rapports » est activée sur votre compte d'équipe.

L'équipe DemarchesTchad
<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\aboub\Desktop\Ges_admin\backend\resources\views/emails/monthly-report.blade.php ENDPATH**/ ?>