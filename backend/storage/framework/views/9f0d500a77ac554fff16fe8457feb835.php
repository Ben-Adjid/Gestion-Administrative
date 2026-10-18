<?php $__env->startComponent('mail::message'); ?>
# 🔐 Réinitialiser votre mot de passe

Bonjour <?php echo new \Illuminate\Support\EncodedHtmlString($user->name); ?>,

Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe.

**Ce lien expire dans <?php echo new \Illuminate\Support\EncodedHtmlString($expiresIn); ?>.**

<?php $__env->startComponent('mail::button', ['url' => $resetUrl]); ?>
Réinitialiser le mot de passe
<?php echo $__env->renderComponent(); ?>

Ou copiez ce lien dans votre navigateur:
<?php echo new \Illuminate\Support\EncodedHtmlString($resetUrl); ?>


## ⚠️ Important:
- Ce lien est valide une seule fois
- Ne partagez jamais ce lien avec d'autres
- Si vous n'avez pas demandé cette réinitialisation, ignorez cet email

Cordialement,
L'équipe DemarchesTchad

---

*Email envoyé automatiquement - Ne répondez pas à cet email*
<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\aboub\Desktop\Ges_admin\backend\resources\views/emails/password-reset.blade.php ENDPATH**/ ?>