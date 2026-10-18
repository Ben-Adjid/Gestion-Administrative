<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Démarche prise en charge par un bureau. Le statut dit d'où vient l'information :
 * confirmed (source officielle), press_reported (article de presse), to_verify (non confirmé).
 */
class OfficeService extends Model
{
    public const STATUSES = ['confirmed', 'press_reported', 'to_verify'];

    /** Seuls ces statuts permettent d'afficher le bureau pour une démarche (jamais « to_verify »). */
    public const DISPLAYABLE = ['confirmed', 'press_reported'];

    protected $fillable = ['office_id', 'demarche_id', 'status', 'source_label', 'source_url', 'verified_on'];

    protected $casts = ['verified_on' => 'date:Y-m-d'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function demarche(): BelongsTo
    {
        return $this->belongsTo(Demarche::class);
    }
}
