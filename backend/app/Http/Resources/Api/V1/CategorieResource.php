<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategorieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $language = $request->query('lang');

        return [
            'id' => $this->id,
            'name' => $this->localizedName($language),
            'name_fr' => $this->name,
            'slug' => $this->slug,
            'description' => $this->localizedDescription($language),
            'ministry' => $this->ministry,
            'icon' => $this->icon,
            'color' => $this->color,
            'display_order' => $this->display_order,
            'is_active' => (bool) $this->is_active,
            'demarches_count' => $this->whenCounted('demarches'),
            'translations' => $this->when($request->is('api/v1/admin/*'), fn () => [
                'en' => ['name' => $this->name_en, 'description' => $this->description_en],
                'ar' => ['name' => $this->name_ar, 'description' => $this->description_ar],
            ]),
        ];
    }
}
