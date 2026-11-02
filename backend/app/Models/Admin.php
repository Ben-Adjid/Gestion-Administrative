<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasFactory, HasApiTokens, SoftDeletes;

    public const ROLES = ['super_admin', 'admin', 'editor'];

    /** Rôles autorisés à publier, dépublier, archiver et restaurer, à modérer et à exporter. */
    public const MODERATOR_ROLES = ['super_admin', 'admin'];

    public function isModerator(): bool
    {
        return in_array($this->role, self::MODERATOR_ROLES, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Droits effectifs, miroir exact des middlewares de routes/api.php. Le frontend s'en sert
     * pour construire le menu ; la protection réelle reste celle du serveur.
     */
    public function capabilities(): array
    {
        $moderator = $this->isModerator();

        return [
            'edit_demarches' => true,
            'publish_demarches' => $moderator,
            'archive_demarches' => $moderator,
            'manage_categories' => $moderator,
            'moderate_signalements' => $moderator,
            'export_csv' => $moderator,
            'view_analytics' => $moderator,
            // Villes, bureaux, parcours, calendrier et synonymes
            'manage_reference' => $moderator,
            // Avis, questions et réponses du public
            'moderate_community' => $moderator,
            'verify_demarches' => $moderator,
            'import_demarches' => $moderator,
            // Lecture seule : tout le monde peut voir les recherches sans résultat pour écrire les fiches
            'view_search_misses' => true,
            'manage_team' => $this->isSuperAdmin(),
            'view_audit_log' => $this->isSuperAdmin(),
            'manage_api_keys' => $this->isSuperAdmin(),
        ];
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
        'notify_signalements',
        'notify_community',
        'notify_demarche_updates',
        'notify_admin_reports',
        'notify_newsletter',
        'notification_frequency',
        'last_email_sent_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_email_sent_at' => 'datetime',
        'notify_signalements' => 'boolean',
        'notify_community' => 'boolean',
        'notify_demarche_updates' => 'boolean',
        'notify_admin_reports' => 'boolean',
        'notify_newsletter' => 'boolean',
    ];
}
