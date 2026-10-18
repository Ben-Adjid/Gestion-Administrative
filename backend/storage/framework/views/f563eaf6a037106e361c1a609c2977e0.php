<?php $__env->startComponent('mail::message'); ?>
# Réponse à votre signalement

Bonjour <?php echo new \Illuminate\Support\EncodedHtmlString($signalement->author_name ?: 'Madame, Monsieur'); ?>,

Notre équipe a examiné votre signalement concernant
**<?php echo new \Illuminate\Support\EncodedHtmlString($signalement->demarche?->title ?: 'une démarche administrative'); ?>**.

<?php echo new \Illuminate\Support\EncodedHtmlString($response); ?>


Merci de contribuer à l’amélioration des informations disponibles sur DemarchesTchad.

L’équipe DemarchesTchad
<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\aboub\Desktop\Ges_admin\backend\resources\views/emails/signalement-response.blade.php ENDPATH**/ ?>