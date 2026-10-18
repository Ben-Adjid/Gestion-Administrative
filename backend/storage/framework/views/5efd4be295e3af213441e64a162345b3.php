<?php $__env->startComponent('mail::message'); ?>
# 🎉 Bienvenue sur DemarchesTchad!

Bonjour <?php echo new \Illuminate\Support\EncodedHtmlString($user->name); ?>,

Merci de vous être inscrit sur **DemarchesTchad** - le portail officiel des démarches administratives du Tchad.

## Vos informations de connexion:
- **Email:** <?php echo new \Illuminate\Support\EncodedHtmlString($user->email); ?>

<?php if($password): ?>
- **Mot de passe temporaire:** `<?php echo new \Illuminate\Support\EncodedHtmlString($password); ?>`
<?php endif; ?>

## Prochaines étapes:

1. **Connectez-vous** à votre compte
2. **Explorez les démarches** disponibles
3. **Sauvegardez vos favorites** ❤️
4. **Recevez des notifications** pour les mises à jour

<?php $__env->startComponent('mail::button', ['url' => $loginUrl]); ?>
Se connecter maintenant
<?php echo $__env->renderComponent(); ?>

## Questions?

Si vous avez besoin d'aide, contactez notre support:
- 📧 **Email:** support@demarches.td
- 💬 **Chat:** Disponible 24/7
- 📞 **Téléphone:** +235 XXX XXX XXX

Cordialement,
L'équipe DemarchesTchad

---

**Note:** Ne partagez jamais votre mot de passe avec quiconque.
<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\aboub\Desktop\Ges_admin\backend\resources\views/emails/welcome.blade.php ENDPATH**/ ?>