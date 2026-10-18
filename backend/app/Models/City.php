<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasLocalizedFields;

    protected $fillable = ['name', 'name_ar', 'slug', 'province', 'geo_unit_id', 'is_active', 'display_order'];

    protected $casts = ['is_active' => 'boolean', 'display_order' => 'integer'];

    /** Localité du référentiel géographique rattachée à cette ville. */
    public function geoUnit(): BelongsTo
    {
        return $this->belongsTo(GeoUnit::class);
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('display_order')->orderBy('name');
    }
}
