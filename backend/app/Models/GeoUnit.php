<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unité du référentiel géographique national : province, département, commune ou localité.
 * Une unité n'est « officielle » que si une source officielle la confirme ; sinon elle reste « à vérifier ».
 */
class GeoUnit extends Model
{
    public const LEVELS = ['province', 'department', 'commune', 'locality'];
    public const STATUSES = ['official', 'to_verify'];

    protected $fillable = ['parent_id', 'level', 'name', 'name_ar', 'slug', 'status', 'source_label', 'source_url', 'verified_on', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'verified_on' => 'date:Y-m-d'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Chemin hiérarchique lisible : « Logone Occidental › Moundou ». */
    public function path(): string
    {
        $names = [];
        for ($unit = $this; $unit !== null; $unit = $unit->parent) {
            array_unshift($names, $unit->name);
        }

        return implode(' › ', $names);
    }
}
