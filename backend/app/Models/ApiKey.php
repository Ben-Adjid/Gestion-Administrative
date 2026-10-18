<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Clé API d'un partenaire : seule l'empreinte est conservée. */
class ApiKey extends Model
{
    protected $fillable = ['name', 'key_prefix', 'key_hash', 'requests_count', 'last_used_at', 'is_active', 'created_by'];

    protected $hidden = ['key_hash'];

    protected $casts = ['requests_count' => 'integer', 'last_used_at' => 'datetime', 'is_active' => 'boolean'];

    /** Crée une clé et renvoie [modèle, clé en clair] — la clé en clair n'est jamais stockée. */
    public static function generate(string $name, ?int $adminId): array
    {
        $plain = 'dtk_' . Str::random(40);
        $key = static::create([
            'name' => $name,
            'key_prefix' => substr($plain, 0, 12),
            'key_hash' => hash('sha256', $plain),
            'created_by' => $adminId,
        ]);

        return [$key, $plain];
    }

    public static function findActive(?string $plain): ?self
    {
        if (!$plain || !str_starts_with($plain, 'dtk_')) {
            return null;
        }

        return static::where('key_hash', hash('sha256', $plain))->where('is_active', true)->first();
    }
}
