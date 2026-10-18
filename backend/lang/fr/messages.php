<?php

// Messages d'erreur métier renvoyés par l'API (langue choisie via Accept-Language).
return [
    'account_locked' => 'Compte temporairement verrouillé. Veuillez réessayer plus tard.',
    'invalid_credentials' => 'Email ou mot de passe incorrect.',
    'reset_link_invalid' => 'Lien de réinitialisation invalide ou expiré.',
    'current_password_wrong' => 'Le mot de passe actuel est incorrect.',
    'documents_limit' => 'Vous avez atteint la limite de 50 documents suivis.',
    'category_not_empty' => 'Impossible de supprimer une catégorie qui contient des démarches : déplacez-les ou désactivez la catégorie.',
    'self_role' => 'Vous ne pouvez pas modifier votre propre rôle ni désactiver votre propre compte.',
    'last_super_admin' => 'Il doit toujours rester au moins un super administrateur actif.',
    'self_delete' => 'Vous ne pouvez pas supprimer votre propre compte.',
    'status_moderator_only' => 'Seul un administrateur peut changer le statut de publication.',
    'forbidden' => 'Action non autorisée. Vous ne disposez pas des privilèges nécessaires.',
    'citizen_only' => 'Cette action est réservée aux comptes citoyens actifs.',
    'account_deleted' => 'Votre compte et toutes ses données ont été supprimés.',
    'signalement_sent' => 'Votre signalement a été transmis à l’équipe. Merci pour votre contribution.',
    'signalement_limit' => 'Vous avez déjà envoyé 3 signalements cette heure-ci. Réessayez dans :minutes minute.|Vous avez déjà envoyé 3 signalements cette heure-ci. Réessayez dans :minutes minutes.',
    'submission_limit' => 'Vous avez atteint la limite d’envois pour cette heure. Réessayez dans :minutes minute.|Vous avez atteint la limite d’envois pour cette heure. Réessayez dans :minutes minutes.',
    'review_received' => 'Merci ! Votre avis sera publié après vérification par l’équipe.',
    'question_received' => 'Merci ! Votre question sera publiée après vérification par l’équipe.',
    'answer_received' => 'Merci ! Votre réponse sera publiée après vérification par l’équipe.',
    'import_invalid_file' => 'Le fichier doit être un tableur Excel (.xlsx) ou un fichier CSV.',
    'import_unknown_category' => 'Catégorie inconnue : « :value ».',
    'import_missing_field' => 'Champ obligatoire manquant : :field.',
    'import_duplicate' => 'Une démarche porte déjà ce titre.',
    'city_has_offices' => 'Impossible de supprimer une ville qui contient des bureaux : supprimez ou déplacez-les d’abord.',
];
