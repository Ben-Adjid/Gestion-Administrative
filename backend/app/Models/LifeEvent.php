<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Parcours de vie : une situation (naissance, mariage…) et ses démarches dans l'ordre. */
class LifeEvent extends Model
{
    use HasLocalizedFields;

    protected $fillable = [
        'slug', 'title', 'title_en', 'title_ar', 'description', 'description_en', 'description_ar',
        'icon', 'display_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean', 'display_order' => 'integer'];

    public function demarches(): BelongsToMany
    {
        return $this->belongsToMany(Demarche::class, 'life_event_demarche')
            ->withPivot('position')
            ->orderBy('life_event_demarche.position');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('display_order')->orderBy('title');
    }
}
