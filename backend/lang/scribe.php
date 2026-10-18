<?php

// Libellés de la documentation de l'API publique (Scribe), en français.
return [
    'labels' => [
        'search' => 'Rechercher',
        'base_url' => 'Adresse de base',
    ],

    'auth' => [
        'none' => 'Aucune authentification n’est nécessaire. Une clé partenaire facultative, envoyée dans l’en-tête **`X-Api-Key`**, supprime la limite de 100 requêtes par minute.',
        'instruction' => [
            'query' => 'Pour vous authentifier, ajoutez le paramètre **`:parameterName`** à l’adresse de la requête.',
            'body' => 'Pour vous authentifier, ajoutez le paramètre **`:parameterName`** au corps de la requête.',
            'query_or_body' => 'Pour vous authentifier, ajoutez le paramètre **`:parameterName`** à l’adresse ou au corps de la requête.',
            'bearer' => 'Pour vous authentifier, ajoutez l’en-tête **`Authorization`** avec la valeur **`"Bearer :placeholder"`**.',
            'basic' => 'Pour vous authentifier, ajoutez l’en-tête **`Authorization`** de la forme **`"Basic {identifiants}"`** (identifiant et mot de passe séparés par deux-points, encodés en base64).',
            'header' => 'Pour vous authentifier, ajoutez l’en-tête **`:parameterName`** avec la valeur **`":placeholder"`**.',
        ],
        'details' => 'Les points d’accès qui exigent une authentification portent la mention « authentification requise ».',
    ],

    'headings' => [
        'introduction' => 'Introduction',
        'auth' => 'Authentification',
    ],

    'endpoint' => [
        'request' => 'Requête',
        'headers' => 'En-têtes',
        'url_parameters' => 'Paramètres d’adresse',
        'body_parameters' => 'Paramètres du corps',
        'query_parameters' => 'Paramètres de requête',
        'response' => 'Réponse',
        'response_fields' => 'Champs de la réponse',
        'example_request' => 'Exemple de requête',
        'example_response' => 'Exemple de réponse',
        'responses' => [
            'binary' => 'Données binaires',
            'empty' => 'Réponse vide',
        ],
    ],

    'try_it_out' => [
        'open' => 'Essayer',
        'cancel' => 'Annuler',
        'send' => 'Envoyer la requête',
        'loading' => 'Envoi…',
        'received_response' => 'Réponse reçue',
        'request_failed' => 'La requête a échoué',
        'error_help' => 'Vérifiez votre connexion. Les détails de l’erreur sont dans la console du navigateur.',
    ],

    'links' => [
        'postman' => 'Collection Postman',
        'openapi' => 'Spécification OpenAPI',
    ],
];
