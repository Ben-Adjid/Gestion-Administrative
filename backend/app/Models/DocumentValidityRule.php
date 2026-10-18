<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** Règle de validité d'un document administratif, avec sa source et son niveau de confirmation. */
class DocumentValidityRule extends Model
{
    use HasLocalizedFields;

    public const CATEGORIES = ['identity', 'travel', 'transport', 'civil_status', 'justice', 'other'];
    public const STARTS = ['issue_date', 'not_applicable', 'unknown'];
    public const STATUSES = ['confirmed', 'secondary', 'to_verify', 'business_requirement', 'permanent', 'status_based'];

    protected $fillable = [
        'key', 'name', 'name_en', 'name_ar', 'country_code', 'category', 'demarche_id',
        'validity_months', 'validity_start', 'renewal', 'renewal_en', 'renewal_ar', 'notes', 'notes_en', 'notes_ar',
        'status', 'source_label', 'source_url', 'source_date', 'verified_on', 'position', 'is_active',
    ];

    protected $casts = [
        'validity_months' => 'integer',
        'source_date' => 'date:Y-m-d',
        'verified_on' => 'date:Y-m-d',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    public function demarche(): BelongsTo
    {
        return $this->belongsTo(Demarche::class);
    }

    /**
     * Une date d'expiration n'est calculable que si la durée est sourcée et part de la date de délivrance.
     * Jamais pour une règle « à vérifier » : on ne crée pas de fausse échéance.
     */
    /**
     * Une expiration n'est calculée que si la durée existe, part de la délivrance, et que le statut
     * le permet. « permanent » et « status_based » ne calculent JAMAIS d'expiration (aucune fausse date).
     */
    public function canComputeExpiry(): bool
    {
        return $this->validity_months !== null
            && $this->validity_start === 'issue_date'
            && ! in_array($this->status, ['to_verify', 'permanent', 'status_based'], true);
    }

    /** Vrai pour une exigence métier de l'équipe : la durée n'est pas une règle officielle confirmée. */
    public function isBusinessRequirement(): bool
    {
        return $this->status === 'business_requirement';
    }

    public function expiryFor(Carbon $issuedOn): ?Carbon
    {
        return $this->canComputeExpiry() ? $issuedOn->copy()->addMonthsNoOverflow($this->validity_months) : null;
    }
}
