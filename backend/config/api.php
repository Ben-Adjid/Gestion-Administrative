<?php

return [
    // Limite de lecture publique sans clé (cahier des charges, module 9 : 100 requêtes/minute par IP).
    'public_per_minute' => (int) env('API_PUBLIC_PER_MINUTE', 100),

    // Secret partagé avec le serveur Next.js (variable INTERNAL_API_KEY des deux côtés) : ses requêtes
    // de pré-génération ne sont pas limitées. Laisser vide désactive ce passe-droit.
    'internal_key' => env('INTERNAL_API_KEY'),
];
