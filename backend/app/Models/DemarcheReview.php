<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Retour d'expérience d'un citoyen sur une démarche, publié après modération. */
class DemarcheReview extends Model
{
    protected $fillable = [
        'demarche_id', 'city_id', 'visit_date', 'price_paid', 'duration_days', 'rating', 'comment',
        'author_name', 'status', 'ip_hash', 'moderated_by', 'moderated_at',
    ];

    protected $casts = [
        'visit_date' => 'date:Y-m-d',
        'price_paid' => 'integer',
        'duration_days' => 'integer',
        'rating' => 'integer',
        'moderated_at' => 'datetime',
    ];

    public function demarche(): BelongsTo
    {
        return $this->belongsTo(Demarche::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
