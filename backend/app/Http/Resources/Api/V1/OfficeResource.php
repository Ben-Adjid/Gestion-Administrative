<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Office */
class OfficeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $language = Office::requestLanguage();
        $isAdmin = $request->is('api/v1/admin/*');

        return [
            'id' => $this->id,
            'name' => $this->localized('name', $language),
            'address' => $language === 'ar' && filled($this->address_ar) ? $this->address_ar : $this->address,
            'opening_hours' => $this->localized('opening_hours', $language),
            'phone' => $this->phone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'directions_url' => $this->directionsUrl(),
            'source_url' => $this->source_url,
            'is_active' => (bool) $this->is_active,
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'slug' => $this->city->slug,
                'name' => $this->city->localized('name', $language),
            ]),
            // Valeurs brutes et fiches liées pour le formulaire de l'administration.
            'raw' => $this->when($isAdmin, fn () => $this->only([
                'city_id', 'name', 'name_en', 'name_ar', 'address', 'address_ar',
                'opening_hours', 'opening_hours_en', 'opening_hours_ar',
            ])),
            'demarche_ids' => $this->when($isAdmin && $this->relationLoaded('demarches'), fn () => $this->demarches->pluck('id')->all()),
        ];
    }
}
