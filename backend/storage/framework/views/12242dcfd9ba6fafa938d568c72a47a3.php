<?php $__env->startComponent('mail::message'); ?>
# Merci pour votre signalement

Bonjour<?php echo new \Illuminate\Support\EncodedHtmlString($signalement->author_name ? ' ' . $signalement->author_name : ''); ?>,

Nous avons bien reçu votre signalement (n° <?php echo new \Illuminate\Support\EncodedHtmlString($signalement->id); ?>)<?php echo new \Illuminate\Support\EncodedHtmlString($demarche ? ' concernant la fiche « ' . $demarche->title . ' »' : ''); ?>.

> <?php echo new \Illuminate\Support\EncodedHtmlString(\Illuminate\Support\Str::limit($signalement->message, 500)); ?>


L'équipe va le vérifier et corriger la fiche si nécessaire. Si une réponse est utile, elle vous sera envoyée à cette adresse.

<?php $__env->startComponent('mail::button', ['url' => $siteUrl]); ?>
Retourner sur DemarchesTchad
<?php echo $__env->renderComponent(); ?>

Vous recevez cet email parce que vous avez indiqué votre adresse dans le formulaire de signalement.

L'équipe DemarchesTchad
<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\aboub\Desktop\Ges_admin\backend\resources\views/emails/signalement-received.blade.php ENDPATH**/ ?>