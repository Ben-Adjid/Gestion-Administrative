<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemarcheResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // ?lang=en|ar : les champs textuels sont servis traduits (repli sur le français si absent).
        $language = in_array($request->query('lang'), ['en', 'ar'], true) ? $request->query('lang') : 'fr';
        $content = $this->getTranslatedContent($language);

        return [
            'id' => $this->id,
            'language' => $language,
            'is_translated' => $language === 'fr' || $this->hasTranslation($language),
            'title' => $content['title'],
            'slug' => $this->slug,
            'summary' => $content['summary'],
            'description' => $content['description'],
            'required_documents' => $content['required_documents'] ?? [],
            'procedure_steps' => $content['procedure_steps'] ?? [],
            'conditions' => $content['conditions'] ?? [],
            'cost' => $content['cost'],
            'cost_amount' => $this->cost_amount !== null ? (float) $this->cost_amount : null,
            // Frais annexes (photos, timbres…) et estimation du coût total à prévoir.
            'extra_costs' => collect($this->extra_costs ?? [])->map(fn (array $item) => [
                'label' => ($language !== 'fr' && filled($item["label_{$language}"] ?? null)) ? $item["label_{$language}"] : ($item['label'] ?? ''),
                'amount' => (int) ($item['amount'] ?? 0),
            ])->values()->all(),
            'cost_estimate' => $this->costEstimate(),
            'offices' => OfficeResource::collection($this->whenLoaded('offices')),
            'processing_time' => $content['processing_time'],
            'validity' => $content['validity'],
            'processing_time_days' => $this->processing_time_days,
            'institution' => $content['institution'],
            'authority_level' => $this->authority_level,
            'location' => $content['location'],
            // Ville à laquelle se rapporte « location » (null : texte valable quelle que soit la ville).
            'location_city' => $this->location_city_id && $this->locationCity ? [
                'slug' => $this->locationCity->slug,
                'name' => $this->locationCity->localized('name', $language),
            ] : null,
            'location_city_id' => $this->when($request->is('api/v1/admin/*'), $this->location_city_id),
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'official_source_url' => $this->official_source_url,
            'last_verified_at' => $this->last_verified_at?->toDateString(),
            'status' => $this->status,
            'scheduled_publish_at' => $this->when($request->is('api/v1/admin/*'), fn () => $this->scheduled_publish_at?->toISOString()),
            'is_online' => (bool) $this->is_online,
            'online_url' => $this->online_url,
            'views_count' => (int) $this->views_count,
            'categorie' => new CategorieResource($this->whenLoaded('categorie')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            // Valeurs brutes pour le formulaire d'édition de l'administration uniquement.
            'extra_costs_raw' => $this->when($request->is('api/v1/admin/*'), fn () => $this->extra_costs ?? []),
            'office_ids' => $this->when($request->is('api/v1/admin/*') && $this->relationLoaded('offices'), fn () => $this->offices->pluck('id')->all()),
            'translations' => $this->when($request->is('api/v1/admin/*'), fn () => collect(['en', 'ar'])->mapWithKeys(fn ($language) => [
                $language => collect(\App\Models\Demarche::TRANSLATABLE)
                    ->mapWithKeys(fn ($field) => [$field => $this->getAttribute("{$field}_{$language}")])
                    ->all(),
            ])->all()),
        ];
    }
}
