<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSignalementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Tout citoyen peut soumettre un signalement
    }

    public function rules(): array
    {
        return [
            'demarche_id' => ['nullable', 'exists:demarches,id'],
            'type' => ['required', 'string', 'in:info_erronee,demarche_obsolete,lien_mort,cout_different,demarche_manquante,autre'],
            'author_name' => ['nullable', 'string', 'max:100'],
            'author_email' => ['nullable', 'email', 'max:150'],
            'author_phone' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'min:10', 'max:2500'],
        ];
    }

    // Messages : fichiers lang/{fr,en,ar}/validation.php selon Accept-Language.
}
