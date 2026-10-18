<?php $__env->startComponent('mail::message'); ?>
# Réponse à votre question

Bonjour<?php echo new \Illuminate\Support\EncodedHtmlString($question->author_name ? ' ' . $question->author_name : ''); ?>,

Vous avez posé cette question sur DemarchesTchad :

> <?php echo new \Illuminate\Support\EncodedHtmlString(\Illuminate\Support\Str::limit($question->body, 500)); ?>


**Réponse de l'équipe :**

<?php echo new \Illuminate\Support\EncodedHtmlString($answer->body); ?>


<?php $__env->startComponent('mail::button', ['url' => $url]); ?>
Voir la question en ligne
<?php echo $__env->renderComponent(); ?>

Informations données à titre indicatif : vérifiez toujours auprès du service concerné.

L'équipe DemarchesTchad
<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\aboub\Desktop\Ges_admin\backend\resources\views/emails/question-answered.blade.php ENDPATH**/ ?>