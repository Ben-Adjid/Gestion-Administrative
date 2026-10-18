<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDocument extends Model
{
    use HasFactory;

    public const TYPES = [
        'cni' => "Carte nationale d'identité",
        'passeport' => 'Passeport',
        'permis' => 'Permis de conduire',
        'carte_grise' => 'Carte grise',
        'visa' => 'Visa',
        'titre_sejour' => 'Titre de séjour',
        'autre' => 'Autre document',
    ];

    /** Paliers de rappel du cahier des charges (module 7) : 6 mois, 3 mois, 1 mois, 1 semaine. */
    public const REMINDER_DAYS = [180, 90, 30, 7];

    protected $fillable = ['type', 'label', 'expiration_date', 'issue_date', 'validity_rule_id'];

    protected $casts = [
        'expiration_date' => 'date',
        'issue_date' => 'date',
        'notified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Vrai si le document a une date d'expiration (un acte permanent ou lié au statut n'en a pas). */
    public function hasExpiry(): bool
    {
        return $this->expiration_date !== null;
    }

    /** Jours restants, ou null pour un document sans date d'expiration (jamais de fausse échéance). */
    public function daysUntilExpiration(): ?int
    {
        if (! $this->hasExpiry()) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiration_date->copy()->startOfDay(), false);
    }

    /** Palier atteint aujourd'hui (le plus petit seuil >= jours restants), ou null si aucun. */
    public function dueReminderThreshold(): ?int
    {
        $days = $this->daysUntilExpiration();
        if ($days === null || $days < 0) {
            return null;
        }

        $due = null;
        foreach (self::REMINDER_DAYS as $threshold) {
            if ($days <= $threshold) {
                $due = $threshold;
            }
        }

        if ($due === null || ($this->last_reminder_days !== null && $this->last_reminder_days <= $due)) {
            return null;
        }

        return $due;
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->whereBetween('expiration_date', [now()->startOfDay(), now()->addDays($days)->endOfDay()]);
    }

    public function displayName(): string
    {
        return $this->label ?: (self::TYPES[$this->type] ?? $this->type);
    }
}
