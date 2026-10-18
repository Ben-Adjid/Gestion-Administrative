<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Date du calendrier administratif (inscriptions, examens, rentrée…). */
class CalendarEvent extends Model
{
    use HasLocalizedFields;

    public const CATEGORIES = ['elections', 'education', 'exams', 'identity', 'tax', 'other'];

    /** Types d'échéance : période (début + fin), date limite, événement à date unique. */
    public const KINDS = ['period', 'deadline', 'event'];

    protected $fillable = [
        'title', 'title_en', 'title_ar', 'description', 'description_en', 'description_ar',
        'category', 'kind', 'starts_on', 'ends_on', 'demarche_id', 'source_url', 'is_published',
    ];

    // Format AAAA-MM-JJ : une date seule ne doit pas glisser d'un jour à cause du fuseau horaire.
    protected $casts = ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d', 'is_published' => 'boolean'];

    public function demarche(): BelongsTo
    {
        return $this->belongsTo(Demarche::class);
    }

    /** Événements publiés en cours ou à venir. */
    public function scopeUpcoming(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('is_published', true)
            ->where(fn ($q) => $q->where('starts_on', '>=', $today)->orWhere('ends_on', '>=', $today))
            ->orderBy('starts_on');
    }
}
