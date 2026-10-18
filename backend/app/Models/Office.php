<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    use HasLocalizedFields;

    protected $fillable = [
        'city_id', 'name', 'name_en', 'name_ar', 'address', 'address_ar',
        'opening_hours', 'opening_hours_en', 'opening_hours_ar', 'phone', 'latitude', 'longitude', 'source_url', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** Démarches prises en charge par ce bureau, avec leur statut de confirmation. */
    public function services(): HasMany
    {
        return $this->hasMany(OfficeService::class);
    }

    public function demarches(): BelongsToMany
    {
        return $this->belongsToMany(Demarche::class, 'demarche_office');
    }

    /** Lien d'itinéraire Google Maps : coordonnées si connues, sinon recherche par nom et adresse. */
    /**
     * Itinéraire seulement quand l'emplacement est réellement connu (coordonnées ou adresse écrite) ;
     * une recherche sur le seul nom du bureau pourrait mener ailleurs : null dans ce cas.
     */
    public function directionsUrl(): ?string
    {
        if ($this->latitude !== null && $this->longitude !== null) {
            $destination = "{$this->latitude},{$this->longitude}";
        } elseif (filled($this->address)) {
            $destination = implode(', ', array_filter([$this->name, $this->address, $this->city?->name, 'Tchad']));
        } else {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($destination);
    }
}
