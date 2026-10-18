<?php

namespace App\Providers;

use App\Models\ApiKey;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * Lecture publique de l'API (cahier des charges, module 9) :
         *  - 100 requêtes/minute par adresse IP sans clé ;
         *  - illimité avec une clé partenaire valide (en-tête X-Api-Key), usage compté ;
         *  - illimité pour le serveur du site lui-même (en-tête X-Internal-Key = INTERNAL_API_KEY),
         *    qui pré-génère toutes les pages depuis une seule adresse IP.
         */
        RateLimiter::for('public-read', function (Request $request) {
            $internal = (string) config('api.internal_key');
            if ($internal !== '' && hash_equals($internal, (string) $request->header('X-Internal-Key'))) {
                return Limit::none();
            }

            if ($key = ApiKey::findActive($request->header('X-Api-Key'))) {
                $key->increment('requests_count', 1, ['last_used_at' => now()]);

                return Limit::none();
            }

            return Limit::perMinute((int) config('api.public_per_minute', 100))->by('public-read|' . $request->ip());
        });
    }
}
