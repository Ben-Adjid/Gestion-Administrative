<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'admin_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * Méthode statique pour créer rapidement un log d'activité en évitant les données sensibles
     */
    public static function logAction(
        ?int $adminId,
        string $action,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        // Filtrage de sécurité : ne jamais consigner des mots de passe ou tokens
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'remember_token', 'api_token'];
        
        $sanitize = function (?array $data) use ($sensitiveKeys) {
            if (!$data) return null;
            foreach ($sensitiveKeys as $key) {
                if (isset($data[$key])) {
                    $data[$key] = '********';
                }
            }
            return $data;
        };

        return self::create([
            'admin_id' => $adminId,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'old_values' => $sanitize($oldValues),
            'new_values' => $sanitize($newValues),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);
    }
}
