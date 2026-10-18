<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Synonyme extends Model
{
    protected $table = 'synonymes';

    protected $fillable = [
        'term',
        'synonym',
        'description',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTerm($query, $term)
    {
        return $query->whereRaw('LOWER(term) = LOWER(?)', [trim($term)]);
    }

    public function scopeByBothDirections($query, $term)
    {
        $term = trim($term);
        return $query->whereRaw('LOWER(term) = LOWER(?) OR LOWER(synonym) = LOWER(?)', [$term, $term]);
    }
}
