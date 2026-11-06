<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Categorie;
use App\Models\Demarche;
use App\Http\Resources\Api\V1\DemarcheResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminDemarcheControllerFull extends Controller
{
    private const LOGGED_FIELDS = ['title', 'status', 'categorie_id', 'cost', 'processing_time', 'validity', 'institution', 'location'];

    /** Champs conservés à chaque modification : suffisants pour restaurer une version antérieure (sans le statut). */
    private const SNAPSHOT_FIELDS = [
        'title', 'summary', 'description', 'categorie_id', 'cost', 'cost_amount', 'processing_time', 'validity',
        'institution', 'location', 'official_source_url', 'required_documents', 'procedure_steps', 'conditions', 'status',
    ];

    private function isLastEditor(Request $request, Demarche $demarche): bool
    {
        return $demarche->updated_by !== null && (int) $demarche->updated_by === (int) $request->user()->id;
    }

    /** Vrai seulement si celui qui a modifié la fiche veut la publier et qu'un autre administrateur peut relire. */
    private function mustBeReviewedByAnother(Request $request, Demarche $demarche): bool
    {
        return $this->isLastEditor($request, $demarche)
            && \App\Models\Admin::where('is_active', true)->whereIn('role', ['super_admin', 'admin'])
                ->where('id', '!=', $request->user()->id)->exists();
    }

    /** GET /api/v1/admin/demarches/{demarche}/history : versions journalisées (modérateurs). */
    public function history(Request $request, Demarche $demarche)
    {
        if (!$request->user()->isModerator()) {
            return response()->json(['message' => 'Réservé aux modérateurs.'], 403);
        }

        $entries = ActivityLog::where('subject_type', Demarche::class)
            ->where('subject_id', $demarche->id)
            ->where('action', 'update_demarche')
            ->latest('id')->limit(30)->get(['id', 'description', 'old_values', 'new_values', 'created_at', 'admin_id']);

        return response()->json(['data' => $entries]);
    }

    /**
     * POST /api/v1/admin/demarches/{demarche}/restore/{entry} : remet la fiche dans l'état d'avant une modification.
     * Le statut n'est jamais restauré : publier ou dépublier reste une décision distincte, soumise aux mêmes garde-fous.
     */
    public function restoreVersion(Request $request, Demarche $demarche, ActivityLog $entry)
    {
        if (!$request->user()->isModerator()) {
            return response()->json(['message' => 'Réservé aux modérateurs.'], 403);
        }
        if ($entry->subject_type !== Demarche::class || (int) $entry->subject_id !== (int) $demarche->id
            || $entry->action !== 'update_demarche' || empty($entry->old_values)) {
            return response()->json(['message' => 'Cette entrée ne permet pas de restaurer la fiche.'], 422);
        }

        $before = $demarche->only(self::SNAPSHOT_FIELDS);
        $restorable = array_diff_key($entry->old_values, array_flip(['status']));
        $demarche->update($restorable + ['updated_by' => $request->user()->id]);
        $this->log($request, 'restore_demarche', "Restauration de la démarche « {$demarche->title} » (version du {$entry->created_at})", $demarche, $before, $demarche->only(self::SNAPSHOT_FIELDS));

        return response()->json([
            'message' => 'Fiche restaurée. Son statut (publiée ou non) n’a pas changé.',
            'data' => new DemarcheResource($demarche->fresh()->load(['categorie', 'offices'])),
        ]);
    }

    private const TEXT_TRANSLATIONS = ['title' => 255, 'summary' => 500, 'description' => 20000, 'cost' => 100, 'processing_time' => 100, 'validity' => 255, 'institution' => 255, 'location' => 255];
    private const LIST_TRANSLATIONS = ['required_documents', 'procedure_steps', 'conditions'];

    /** Règles de validation des champs traduits (_en, _ar), tous facultatifs. */
    private function translationRules(): array
    {
        $rules = [];
        foreach (['en', 'ar'] as $language) {
            foreach (self::TEXT_TRANSLATIONS as $field => $max) {
                $rules["{$field}_{$language}"] = "nullable|string|max:{$max}";
            }
            foreach (self::LIST_TRANSLATIONS as $field) {
                $rules["{$field}_{$language}"] = 'nullable|array';
                $rules["{$field}_{$language}.*"] = 'string|max:1000';
            }
        }

        return $rules;
    }

    private function translationData(array $validated): array
    {
        return array_intersect_key($validated, $this->translationRules());
    }

    /** Frais annexes, organisme compétent et bureaux liés (tous facultatifs). */
    private function extraRules(): array
    {
        return [
            'extra_costs' => 'nullable|array|max:10',
            'extra_costs.*.label' => 'required|string|max:100',
            'extra_costs.*.label_en' => 'nullable|string|max:100',
            'extra_costs.*.label_ar' => 'nullable|string|max:100',
            'extra_costs.*.amount' => 'required|integer|min:0|max:10000000',
            // Niveau de l’organisme compétent et ville à laquelle se rapporte le texte « lieu » de la fiche.
            'authority_level' => 'nullable|in:' . implode(',', Demarche::AUTHORITY_LEVELS),
            'location_city_id' => 'nullable|integer|exists:cities,id',
            'office_ids' => 'nullable|array',
            'office_ids.*' => 'integer|exists:offices,id',
        ];
    }

    /** Champs supplémentaires validés, prêts à enregistrer (sans office_ids, géré à part). */
    private function extraData(array $validated): array
    {
        $data = array_intersect_key($validated, array_flip(['extra_costs', 'authority_level', 'location_city_id']));
        if (array_key_exists('extra_costs', $data)) {
            $data['extra_costs'] = collect($data['extra_costs'] ?? [])->map(fn ($item) => [
                'label' => $item['label'],
                'label_en' => $item['label_en'] ?? null,
                'label_ar' => $item['label_ar'] ?? null,
                'amount' => (int) $item['amount'],
            ])->values()->all();
        }

        return $data;
    }

    /**
     * PATCH /api/v1/admin/demarches/{id}/verify
     * L'équipe confirme que les informations de la fiche sont toujours à jour.
     */
    public function verify(Request $request, Demarche $demarche)
    {
        $demarche->forceFill(['last_verified_at' => now(), 'updated_by' => $request->user()->id])->save();
        $this->log($request, 'verify_demarche', "Fiche « {$demarche->title} » vérifiée", $demarche);

        return response()->json([
            'message' => 'Fiche marquée comme vérifiée.',
            'data' => new DemarcheResource($demarche->load(['categorie', 'offices'])),
        ]);
    }

    private function log(Request $request, string $action, string $description, Demarche $demarche, ?array $old = null, ?array $new = null): void
    {
        ActivityLog::logAction($request->user()->id, $action, $description, Demarche::class, $demarche->id, $old, $new);
    }

    /**
     * GET /api/v1/admin/demarches
     * Liste tous les démarches (avec pagination)
     */
    public function index(Request $request)
    {
        $query = $request->query('trashed') === 'only'
            ? Demarche::onlyTrashed()->with(['categorie', 'offices', 'locationCity'])
            : Demarche::with(['categorie', 'offices', 'locationCity']);

        // Filtrer par statut
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filtrer par catégorie
        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->query('categorie_id'));
        }

        // Recherche par titre/institution
        if ($request->filled('q')) {
            $query->search((string) $request->query('q'));
        }

        // Tri
        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $query->latest();
        $demarches = $query->paginate($perPage);

        return response()->json([
            'data' => DemarcheResource::collection($demarches),
            'meta' => [
                'total' => $demarches->total(),
                'per_page' => $demarches->perPage(),
                'current_page' => $demarches->currentPage(),
                'last_page' => $demarches->lastPage(),
            ]
        ]);
    }

    /**
     * POST /api/v1/admin/demarches
     * Créer une nouvelle démarche
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:demarches,title',
            'summary' => 'nullable|string|max:500',
            'description' => 'required|string',
            'categorie_id' => 'required|exists:categories,id',
            'cost' => 'required|string|max:100',
            'cost_amount' => 'nullable|numeric|min:0',
            'processing_time' => 'nullable|string|max:100',
            'validity' => 'nullable|string|max:255',
            'processing_time_days' => 'nullable|integer|min:0',
            'institution' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'official_source_url' => 'nullable|url',
            'is_online' => 'boolean',
            'online_url' => 'nullable|url',
            'required_documents' => 'nullable|array',
            'required_documents.*' => 'string',
            'procedure_steps' => 'nullable|array',
            'procedure_steps.*' => 'string',
            'conditions' => 'nullable|array',
            'conditions.*' => 'string',
            'status' => 'nullable|in:draft,published',
        ] + $this->translationRules() + $this->extraRules());

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation échouée.', 'errors' => $validator->errors()], 422);
        }

        // Seuls les modérateurs publient : la fiche d'un éditeur reste un brouillon jusqu'à validation.
        $status = $request->user()->isModerator() ? $request->input('status', 'draft') : 'draft';

        $slugBase = Str::slug($request->input('title'));
        $slug = $slugBase;
        $suffix = 1;
        while (Demarche::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$slugBase}-{$suffix}";
            $suffix++;
        }

        $demarche = Demarche::create($this->translationData($validator->validated()) + $this->extraData($validator->validated()) + [
            'title' => $request->input('title'),
            'slug' => $slug,
            'summary' => $request->input('summary'),
            'description' => $request->input('description'),
            'categorie_id' => $request->input('categorie_id'),
            'cost' => $request->input('cost'),
            // Prix unique : montant toujours déduit du coût affiché (jamais deux valeurs contradictoires).
            'cost_amount' => Demarche::amountFromCost($request->input('cost')) ?? $request->input('cost_amount'),
            'processing_time' => $request->input('processing_time'),
            'validity' => $request->input('validity'),
            'processing_time_days' => $request->input('processing_time_days'),
            'institution' => $request->input('institution'),
            'location' => $request->input('location'),
            'contact_email' => $request->input('contact_email'),
            'contact_phone' => $request->input('contact_phone'),
            'official_source_url' => $request->input('official_source_url'),
            'is_online' => $request->boolean('is_online'),
            'online_url' => $request->input('online_url'),
            'required_documents' => $request->input('required_documents', []),
            'procedure_steps' => $request->input('procedure_steps', []),
            'conditions' => $request->input('conditions', []),
            'status' => $status,
            'last_verified_at' => $status === 'published' ? now() : null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $demarche->offices()->sync($validator->validated()['office_ids'] ?? []);

        $this->log($request, 'create_demarche', "Création de la démarche « {$demarche->title} » ({$status})", $demarche, null, $demarche->only(self::LOGGED_FIELDS));

        return response()->json([
            'message' => $status === 'draft' && !$request->user()->isModerator()
                ? 'Brouillon enregistré. Un administrateur doit le valider avant publication.'
                : 'Démarche créée avec succès.',
            'data' => new DemarcheResource($demarche->load(['categorie', 'offices'])),
        ], 201);
    }

    /**
     * GET /api/v1/admin/demarches/{id}
     * Afficher une démarche
     */
    public function show(Demarche $demarche)
    {
        return response()->json([
            'data' => new DemarcheResource($demarche->load(['categorie', 'offices'])),
        ]);
    }

    /**
     * PUT /api/v1/admin/demarches/{id}
     * Mettre à jour une démarche
     */
    public function update(Request $request, Demarche $demarche)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255|unique:demarches,title,' . $demarche->id,
            'summary' => 'nullable|string|max:500',
            'description' => 'string',
            'categorie_id' => 'exists:categories,id',
            'cost' => 'string|max:100',
            'cost_amount' => 'nullable|numeric|min:0',
            'processing_time' => 'nullable|string|max:100',
            'validity' => 'nullable|string|max:255',
            'processing_time_days' => 'nullable|integer|min:0',
            'institution' => 'string|max:255',
            'location' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'official_source_url' => 'nullable|url',
            'is_online' => 'boolean',
            'online_url' => 'nullable|url',
            'required_documents' => 'nullable|array',
            'required_documents.*' => 'string',
            'procedure_steps' => 'nullable|array',
            'procedure_steps.*' => 'string',
            'conditions' => 'nullable|array',
            'conditions.*' => 'string',
            'status' => 'in:draft,published,archived',
            'scheduled_publish_at' => 'nullable|date|after:now',
        ] + $this->translationRules() + $this->extraRules());

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation échouée.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Programmer une publication revient à décider de publier : mêmes droits et même double validation.
        if (!empty($data['scheduled_publish_at'])) {
            if (!$request->user()->isModerator()) {
                return response()->json(['message' => 'Seul un modérateur peut programmer une publication.'], 403);
            }
            if ($this->mustBeReviewedByAnother($request, $demarche)) {
                return response()->json(['message' => 'Programmation refusée : un autre administrateur doit relire la fiche que vous avez modifiée.'], 403);
            }
        }
        // Publier maintenant annule la programmation.
        if (($data['status'] ?? null) === 'published') {
            $data['scheduled_publish_at'] = null;
        }

        // Le statut n'est modifiable que par un modérateur : un éditeur corrige le contenu sans le (dé)publier.
        if (array_key_exists('status', $data) && $data['status'] !== $demarche->status && !$request->user()->isModerator()) {
            return response()->json([
                'message' => __('messages.status_moderator_only'),
            ], 403);
        }
        // Même garde-fou que la publication : source officielle et vérification réelle, jamais une date inventée.
        if (($data['status'] ?? null) === 'published' && $demarche->status !== 'published') {
            if ($this->mustBeReviewedByAnother($request, $demarche)) {
                return response()->json(['message' => 'Publication refusée : un autre administrateur doit publier la fiche que vous avez modifiée.'], 403);
            }
        }

        // Prix unique : le montant numérique suit toujours le coût affiché.
        if (array_key_exists('cost', $data)) {
            $derived = Demarche::amountFromCost($data['cost']);
            if ($derived !== null) {
                $data['cost_amount'] = $derived;
            }
        }

        // L'adresse de la fiche (slug) ne change pas quand on corrige le titre :
        // les liens partagés, les favoris et le référencement restent valides.

        $officeIds = array_key_exists('office_ids', $data) ? ($data['office_ids'] ?? []) : null;
        $data = array_diff_key($data, array_flip(['office_ids', 'extra_costs'])) + $this->extraData($data);

        $old = $demarche->only(self::SNAPSHOT_FIELDS);
        $data['updated_by'] = $request->user()->id;
        $demarche->update($data);
        if ($officeIds !== null) {
            $demarche->offices()->sync($officeIds);
        }

        $this->log($request, 'update_demarche', "Modification de la démarche « {$demarche->title} »", $demarche, $old, $demarche->only(self::SNAPSHOT_FIELDS));

        return response()->json([
            'message' => 'Démarche mise à jour avec succès.',
            'data' => new DemarcheResource($demarche->fresh()->load(['categorie', 'offices'])),
        ]);
    }

    /**
     * DELETE /api/v1/admin/demarches/{id}
     * Archiver une démarche (soft delete, restaurable depuis la corbeille)
     */
    public function destroy(Request $request, Demarche $demarche)
    {
        $demarche->delete();
        $this->log($request, 'archive_demarche', "Archivage de la démarche « {$demarche->title} »", $demarche);

        return response()->json(['message' => 'Démarche archivée avec succès.']);
    }

    /**
     * POST /api/v1/admin/demarches/{id}/restore
     */
    public function restore(Request $request, $id)
    {
        $demarche = Demarche::onlyTrashed()->findOrFail($id);
        $demarche->restore();
        $this->log($request, 'restore_demarche', "Restauration de la démarche « {$demarche->title} »", $demarche);

        return response()->json([
            'message' => 'Démarche restaurée avec succès.',
            'data' => new DemarcheResource($demarche->load(['categorie', 'offices'])),
        ]);
    }

    /**
     * PATCH /api/v1/admin/demarches/{id}/publish
     */
    public function publish(Request $request, Demarche $demarche)
    {
        // Publier ne vérifie pas la fiche : une fiche non vérifiée peut être publiée, avec la mention « non vérifiée ».
        // Double validation : seulement s'il existe un autre administrateur actif pour relire.
        if ($this->mustBeReviewedByAnother($request, $demarche)) {
            return response()->json(['message' => 'Publication refusée : un autre administrateur doit publier la fiche que vous avez modifiée.'], 403);
        }

        $old = $demarche->status;
        $demarche->update([
            'status' => 'published',
            'scheduled_publish_at' => null,
            'updated_by' => $request->user()->id,
        ]);
        $this->log($request, 'publish_demarche', "Publication de la démarche « {$demarche->title} »", $demarche, ['status' => $old], ['status' => 'published']);

        return response()->json([
            'message' => 'Démarche publiée avec succès.',
            'data' => new DemarcheResource($demarche->load(['categorie', 'offices'])),
        ]);
    }

    /**
     * PATCH /api/v1/admin/demarches/{id}/unpublish
     */
    public function unpublish(Request $request, Demarche $demarche)
    {
        $old = $demarche->status;
        $demarche->update(['status' => 'draft', 'updated_by' => $request->user()->id]);
        $this->log($request, 'unpublish_demarche', "Dépublication de la démarche « {$demarche->title} »", $demarche, ['status' => $old], ['status' => 'draft']);

        return response()->json([
            'message' => 'Démarche dépubliée avec succès.',
            'data' => new DemarcheResource($demarche->load(['categorie', 'offices'])),
        ]);
    }

    /**
     * POST /api/v1/admin/demarches/bulk
     * Action groupée : publier, dépublier, archiver ou déplacer plusieurs fiches.
     * Chaque fiche garde ses garde-fous ; les fiches refusées sont renvoyées avec la raison, jamais ignorées en silence.
     */
    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer|distinct|exists:demarches,id',
            'action' => 'required|in:publish,unpublish,archive,move',
            'target_category_id' => ['required_if:action,move', 'nullable', 'integer', 'exists:categories,id'],
        ]);

        $action = $validated['action'];
        if (in_array($action, ['publish', 'unpublish', 'archive'], true) && !$request->user()->isModerator()) {
            return response()->json(['message' => 'Seul un modérateur peut publier, dépublier ou archiver.'], 403);
        }

        $target = $action === 'move' ? Categorie::find($validated['target_category_id']) : null;
        $done = 0;
        $refused = [];

        foreach (Demarche::whereIn('id', $validated['ids'])->get() as $demarche) {
            switch ($action) {
                case 'publish':
                    if ($this->mustBeReviewedByAnother($request, $demarche)) {
                        $refused[] = ['id' => $demarche->id, 'title' => $demarche->title, 'reason' => 'relecture par un autre administrateur requise'];
                        continue 2;
                    }
                    $old = $demarche->status;
                    $demarche->update(['status' => 'published', 'scheduled_publish_at' => null, 'updated_by' => $request->user()->id]);
                    $this->log($request, 'publish_demarche', "Publication de la démarche « {$demarche->title} » (action groupée)", $demarche, ['status' => $old], ['status' => 'published']);
                    break;
                case 'unpublish':
                    $old = $demarche->status;
                    $demarche->update(['status' => 'draft', 'updated_by' => $request->user()->id]);
                    $this->log($request, 'unpublish_demarche', "Dépublication de la démarche « {$demarche->title} » (action groupée)", $demarche, ['status' => $old], ['status' => 'draft']);
                    break;
                case 'archive':
                    $demarche->delete();
                    $this->log($request, 'archive_demarche', "Archivage de la démarche « {$demarche->title} » (action groupée)", $demarche);
                    break;
                case 'move':
                    $old = $demarche->categorie_id;
                    $demarche->update(['categorie_id' => $target->id, 'updated_by' => $request->user()->id]);
                    $this->log($request, 'update_demarche', "Déplacement de la démarche « {$demarche->title} » vers « {$target->name} » (action groupée)", $demarche, ['categorie_id' => $old], ['categorie_id' => $target->id]);
                    break;
            }
            $done++;
        }

        return response()->json([
            'message' => "{$done} démarche(s) traitée(s)." . (count($refused) > 0 ? ' ' . count($refused) . ' refusée(s).' : ''),
            'done' => $done,
            'refused' => $refused,
        ]);
    }

    /**
     * POST /api/v1/admin/demarches/{demarche}/duplicate
     * Copie une fiche en brouillon (même contenu, catégorie et bureaux) pour préparer une fiche voisine.
     */
    public function duplicate(Request $request, Demarche $demarche)
    {
        $base = mb_substr('Copie de ' . $demarche->title, 0, 230);
        $title = $base;
        $n = 2;
        while (Demarche::withTrashed()->where('title', $title)->exists()) {
            $title = $base . ' (' . $n++ . ')';
        }

        $slugBase = Str::slug($title);
        $slug = $slugBase;
        $suffix = 1;
        while (Demarche::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$slugBase}-{$suffix}";
            $suffix++;
        }

        $copy = $demarche->replicate(['slug', 'title', 'status', 'last_verified_at', 'scheduled_publish_at', 'views_count', 'created_by', 'updated_by']);
        $copy->forceFill([
            'title' => $title,
            'slug' => $slug,
            'status' => 'draft',
            'last_verified_at' => null,
            'views_count' => 0,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ])->save();
        $copy->offices()->sync($demarche->offices->pluck('id')->all());

        $this->log($request, 'create_demarche', "Copie de la démarche « {$demarche->title} » en brouillon : « {$copy->title} »", $copy, null, $copy->only(self::LOGGED_FIELDS));

        return response()->json([
            'message' => 'Copie créée en brouillon. Modifiez-la puis publiez-la.',
            'data' => new DemarcheResource($copy->load(['categorie', 'offices'])),
        ], 201);
    }

    /**
     * GET /api/v1/admin/demarches/stats/summary
     * Statistiques récapitulatives
     */
    public function statsSummary()
    {
        return response()->json([
            'total_demarches' => Demarche::count(),
            'published_demarches' => Demarche::where('status', 'published')->count(),
            'draft_demarches' => Demarche::where('status', 'draft')->count(),
            'archived_demarches' => Demarche::where('status', 'archived')->count(),
            'total_views' => Demarche::sum('views_count'),
            'average_views' => Demarche::avg('views_count'),
            'most_viewed' => DemarcheResource::collection(
                Demarche::where('status', 'published')
                    ->orderBy('views_count', 'desc')
                    ->limit(5)
                    ->get()
            ),
        ]);
    }
}
