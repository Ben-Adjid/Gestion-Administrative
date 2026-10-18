<?php

namespace App\Http\Controllers\Api\V1\Citizen;

use App\Http\Controllers\Controller;
use App\Models\UserDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Documents personnels surveillés (module 7). Chaque requête est bornée aux documents du
 * citoyen connecté ; un identifiant appartenant à un autre compte renvoie 404.
 */
class UserDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = $request->user()->documents()->orderBy('expiration_date')->get();

        return response()->json([
            'data' => $documents->map(fn (UserDocument $d) => $this->payload($d))->values(),
            'types' => UserDocument::TYPES,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->documents()->count() >= 50) {
            return response()->json(['message' => __('messages.documents_limit')], 422);
        }

        $document = $request->user()->documents()->create($this->validated($request));

        return response()->json([
            'message' => 'Document ajouté. Vous serez alerté avant son expiration.',
            'data' => $this->payload($document),
        ], 201);
    }

    public function update(Request $request, int $document): JsonResponse
    {
        $model = $request->user()->documents()->findOrFail($document);
        $data = $this->validated($request);

        // Une nouvelle date d'expiration relance le cycle de rappels.
        if ($model->expiration_date?->toDateString() !== $data['expiration_date']) {
            $model->forceFill(['last_reminder_days' => null, 'notified_at' => null]);
        }
        $model->fill($data)->save();

        return response()->json(['message' => 'Document mis à jour.', 'data' => $this->payload($model)]);
    }

    public function destroy(Request $request, int $document): JsonResponse
    {
        $request->user()->documents()->findOrFail($document)->delete();

        return response()->json(['message' => 'Document supprimé.']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(UserDocument::TYPES))],
            'label' => 'nullable|string|max:120',
            'expiration_date' => 'nullable|date_format:Y-m-d|after_or_equal:2000-01-01|before:2100-01-01',
            // Document à règle sourcée : la date d'expiration est calculée à partir de la date de délivrance.
            'validity_rule_id' => 'nullable|integer|exists:document_validity_rules,id',
            'issue_date' => 'nullable|date_format:Y-m-d',
        ]);
        $data = $this->applyRule($data);
        $data['label'] = isset($data['label']) ? trim(strip_tags($data['label'])) ?: null : null;

        return $data;
    }

    /**
     * Document à règle sourcée : la date d'expiration vient du calcul officiel, jamais de la saisie.
     * Sans règle, la date d'expiration reste obligatoire (saisie du citoyen).
     */
    private function applyRule(array $data): array
    {
        if (empty($data['validity_rule_id'])) {
            if (empty($data['expiration_date'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'expiration_date' => ['Indiquez la date d’expiration de ce document.'],
                ]);
            }
            $data['issue_date'] = $data['issue_date'] ?? null;

            return $data;
        }

        $rule = \App\Models\DocumentValidityRule::where('id', $data['validity_rule_id'])->where('is_active', true)->firstOrFail();
        $result = app(\App\Services\DocumentValidityCalculator::class)->evaluate($rule, $data['issue_date'] ?? null);

        // Validité permanente ou liée au statut : aucune date d'expiration, et ce n'est pas une erreur.
        if (in_array($result['state'], [\App\Services\DocumentValidityCalculator::STATE_PERMANENT, \App\Services\DocumentValidityCalculator::STATE_STATUS_BASED], true)) {
            $data['expiration_date'] = null;
            $data['issue_date'] = $data['issue_date'] ?? null;

            return $data;
        }

        if (! $result['can_compute']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'issue_date' => [$result['message']],
            ]);
        }

        $data['expiration_date'] = $result['expiry_date'];

        return $data;
    }

    public static function payload(UserDocument $d): array
    {
        $days = $d->daysUntilExpiration();

        return [
            'id' => $d->id,
            'type' => $d->type,
            'type_label' => UserDocument::TYPES[$d->type] ?? $d->type,
            'label' => $d->label,
            'display_name' => $d->displayName(),
            'expiration_date' => $d->expiration_date?->toDateString(),
            'issue_date' => $d->issue_date?->toDateString(),
            'validity_rule_id' => $d->validity_rule_id,
            'days_remaining' => $days,
            // Sans date d'expiration : « no_expiry » (acte permanent ou lié au statut), jamais une échéance inventée.
            'state' => $days === null ? 'no_expiry' : ($days < 0 ? 'expired' : ($days <= 30 ? 'urgent' : ($days <= 180 ? 'soon' : 'ok'))),
            'last_reminder_days' => $d->last_reminder_days,
            // État détaillé pour les documents à règle officielle ou exigence métier (null sinon).
            'validity' => self::validityPayload($d),
        ];
    }

    /** Résultat du calcul de validité, avec la règle appliquée et sa nature (officielle, exigence métier…). */
    private static function validityPayload(UserDocument $d): ?array
    {
        if ($d->validity_rule_id === null) {
            return null;
        }
        $rule = \App\Models\DocumentValidityRule::find($d->validity_rule_id);
        if ($rule === null) {
            return null;
        }
        $result = app(\App\Services\DocumentValidityCalculator::class)->evaluate($rule, $d->issue_date?->toDateString());

        return $result + [
            'rule_name' => $rule->name,
            'rule_status' => $rule->status,
            'is_business_requirement' => $rule->isBusinessRequirement(),
            'source_url' => $rule->source_url,
        ];
    }
}
