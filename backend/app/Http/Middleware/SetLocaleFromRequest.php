<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique la langue choisie par l'utilisateur (en-tête Accept-Language envoyé par le frontend)
 * aux messages de l'API, notamment les erreurs de validation.
 */
class SetLocaleFromRequest
{
    private const SUPPORTED = ['fr', 'en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $preferred = $request->getPreferredLanguage(self::SUPPORTED);
        app()->setLocale(in_array($preferred, self::SUPPORTED, true) ? $preferred : config('app.fallback_locale', 'fr'));

        return $next($request);
    }
}
