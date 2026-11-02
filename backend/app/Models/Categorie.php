<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Categorie extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
        'slug',
        'description',
        'description_en',
        'description_ar',
        'ministry',
        'icon',
        'color',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relation avec les démarches associées.
     */
    public function demarches(): HasMany
    {
        return $this->hasMany(Demarche::class, 'categorie_id');
    }

    /**
     * Démarches actives et publiées de cette catégorie.
     */
    public function publishedDemarches(): HasMany
    {
        return $this->hasMany(Demarche::class, 'categorie_id')
            ->where('status', 'published');
    }

    /**
     * Scope pour filtrer les catégories actives triées par ordre d'affichage.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('display_order', 'asc');
    }

    /** Nom dans la langue demandée, avec repli sur le français. */
    public function localizedName(?string $language): string
    {
        return match ($language) {
            'en' => $this->name_en ?: $this->name,
            'ar' => $this->name_ar ?: $this->name,
            default => $this->name,
        };
    }

    public function localizedDescription(?string $language): ?string
    {
        return match ($language) {
            'en' => $this->description_en ?: $this->description,
            'ar' => $this->description_ar ?: $this->description,
            default => $this->description,
        };
    }
}
