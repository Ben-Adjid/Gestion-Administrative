<?php

return [
    // URL publique du frontend Next.js (liens envoyés par email : réinitialisation, rappels).
    'url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/'),
];
