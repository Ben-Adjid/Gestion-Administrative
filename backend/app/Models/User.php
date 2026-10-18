<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes;

    // Les champs de sécurité (bannissement, vérification, jeton de réinitialisation)
    // ne sont jamais remplis en masse : ils sont posés explicitement par le serveur.
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'language',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_banned' => false,
        'language' => 'fr',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'password_reset_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_reset_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean',
        'is_banned' => 'boolean',
    ];

    /**
     * Relations
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function documents()
    {
        return $this->hasMany(UserDocument::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_banned', false);
    }

    /**
     * Accessors
     */
    public function getInitialsAttribute()
    {
        $names = explode(' ', $this->name);
        return strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
    }
}
