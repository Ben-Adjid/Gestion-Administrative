<?php

/*
| Assistant WhatsApp (API WhatsApp Cloud de Meta).
| L'assistant reste désactivé (réponses 404) tant que ces quatre valeurs ne sont pas renseignées.
| Elles s'obtiennent dans le tableau de bord Meta for Developers, produit « WhatsApp ».
*/

return [
    'token' => env('WHATSAPP_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    'app_secret' => env('WHATSAPP_APP_SECRET'),
    'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v20.0'),
];
