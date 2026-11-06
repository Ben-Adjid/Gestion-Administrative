<?php $__env->startComponent('mail::message'); ?>
# <?php echo new \Illuminate\Support\EncodedHtmlString($icon); ?> <?php echo new \Illuminate\Support\EncodedHtmlString($title); ?>


<?php echo new \Illuminate\Support\EncodedHtmlString($recipientName ?: 'Madame, Monsieur'); ?>,

<?php echo new \Illuminate\Support\EncodedHtmlString($message); ?>


<?php $__env->startComponent('mail::button', ['url' => config('app.url') . '/profile']); ?>
Voir votre compte
<?php echo $__env->renderComponent(); ?>

---

**À propos de cet email:** Vous recevez cet email car vous êtes inscrit sur DemarchesTchad.

[Gérer vos préférences](<?php echo new \Illuminate\Support\EncodedHtmlString(config('app.url') . '/settings/notifications'); ?>)
<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\aboub\Desktop\Ges_admin\backend\resources\views/emails/notification.blade.php ENDPATH**/ ?>