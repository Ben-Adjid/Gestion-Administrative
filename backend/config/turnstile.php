<?php

// Cloudflare Turnstile : vérification anti-robot des formulaires publics.
// Tant que TURNSTILE_SECRET_KEY est vide, la vérification est désactivée (développement, sans clés).
return [
    'secret' => env('TURNSTILE_SECRET_KEY'),
    'enabled' => filled(env('TURNSTILE_SECRET_KEY')),
    'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    'timeout' => 5,
];
