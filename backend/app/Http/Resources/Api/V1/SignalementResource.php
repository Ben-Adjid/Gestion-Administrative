<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignalementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Les coordonnées du signalant ne servent qu'au modérateur qui lui répond (moindre privilège).
        $user = $request->user();
        $canSeeContact = $user instanceof \App\Models\Admin && $user->isModerator();

        return [
            'id' => $this->id,
            'demarche_id' => $this->demarche_id,
            'type' => $this->type,
            'author_name' => $canSeeContact ? $this->author_name : null,
            'author_email' => $canSeeContact ? $this->author_email : null,
            'author_phone' => $canSeeContact ? $this->author_phone : null,
            'has_contact' => (bool) $this->author_email,
            'message' => $this->message,
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'demarche_title' => $this->demarche?->title,
            'created_at' => $this->created_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
        ];
    }
}
