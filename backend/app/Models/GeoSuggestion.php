<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Localité signalée par un visiteur, à vérifier avant tout ajout au référentiel. */
class GeoSuggestion extends Model
{
    public const STATUSES = ['new', 'accepted', 'rejected', 'duplicate'];

    protected $fillable = ['name', 'province', 'comment', 'contact_email', 'status', 'admin_note', 'ip_hash', 'reviewed_at'];

    protected $casts = ['reviewed_at' => 'datetime'];
}
