<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GeoSuggestion;
use App\Models\GeoUnit;
use App\Models\OfficeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Administration du référentiel géographique et des services des bureaux.
 * Règle : aucun statut « officiel » ou « confirmé » sans source et date de vérification.
 * Chaque modification est journalisée (qui, quoi, quand) ; un lien retiré laisse une trace.
 */
class AdminGeoController extends Controller
{
    /** Niveau de parent autorisé pour chaque niveau (null = racine). */
    private const PARENT_LEVEL = [
        'province' => null,
        'department' => ['province'],
        'commune' => ['department'],
        'locality' => ['province', 'department', 'commune'],
    ];

    public function index(): JsonResponse
    {
        $units = GeoUnit::orderBy('level')->orderBy('name')->get()->map(fn (GeoUnit $u) => $this->unit($u));
        $services = OfficeService::with(['office.city', 'demarche:id,slug'])->get()->map(fn (OfficeService $s) => $this->service($s));

        return response()->json(['data' => ['units' => $units, 'services' => $services, 'levels' => GeoUnit::LEVELS]]);
    }

    /** Localités signalées par les visiteurs, les plus récentes d'abord. */
    public function suggestions(): JsonResponse
    {
        $items = GeoSuggestion::orderByDesc('created_at')->limit(200)->get()->map(fn (GeoSuggestion $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'province' => $s->province,
            'comment' => $s->comment,
            'contact_email' => $s->contact_email,
            'status' => $s->status,
            'admin_note' => $s->admin_note,
            'created_at' => $s->created_at?->toDateTimeString(),
            'reviewed_at' => $s->reviewed_at?->toDateTimeString(),
        ]);

        return response()->json(['data' => $items]);
    }

    /**
     * Traite un signalement. Accepter ne crée PAS la localité : elle doit être ajoutée au référentiel,
     * avec sa source, par une action distincte.
     */
    public function reviewSuggestion(Request $request, GeoSuggestion $suggestion): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(GeoSuggestion::STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);
        $before = $suggestion->only(['status', 'admin_note']);
        $suggestion->update($data + ['reviewed_at' => now()]);
        ActivityLog::logAction($request->user()->id, 'review_geo_suggestion', "Signalement « {$suggestion->name} » : {$suggestion->status}", GeoSuggestion::class, $suggestion->id, $before, $suggestion->only(['status', 'admin_note']));

        return response()->json(['data' => ['id' => $suggestion->id, 'status' => $suggestion->status, 'admin_note' => $suggestion->admin_note]]);
    }

    public function storeUnit(Request $request): JsonResponse
    {
        $data = $this->validateUnit($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $unit = GeoUnit::create($data);
        ActivityLog::logAction($request->user()->id, 'create_geo_unit', "Ajout de « {$unit->name} » ({$unit->level})", GeoUnit::class, $unit->id);

        return response()->json(['data' => $this->unit($unit)], 201);
    }

    public function updateUnit(Request $request, GeoUnit $unit): JsonResponse
    {
        $before = $unit->only(['name', 'status', 'source_url', 'is_active']);
        $unit->update($this->validateUnit($request, $unit));
        $after = $unit->only(array_keys($before));
        ActivityLog::logAction($request->user()->id, 'update_geo_unit', "Modification de « {$unit->name} »", GeoUnit::class, $unit->id, $before, $after);

        return response()->json(['data' => $this->unit($unit->fresh())]);
    }

    public function storeService(Request $request): JsonResponse
    {
        $data = $this->validateService($request);
        if (OfficeService::where('office_id', $data['office_id'])->where('demarche_id', $data['demarche_id'])->exists()) {
            throw ValidationException::withMessages(['demarche_id' => ['Ce bureau porte déjà cette démarche.']]);
        }
        $service = OfficeService::create($data);
        ActivityLog::logAction($request->user()->id, 'create_office_service', "Service rattaché au bureau {$service->office_id} (démarche {$service->demarche_id}, statut {$service->status})", OfficeService::class, $service->id);

        return response()->json(['data' => $this->service($service->load(['office.city', 'demarche:id,slug']))], 201);
    }

    public function updateService(Request $request, OfficeService $service): JsonResponse
    {
        $before = $service->only(['status', 'source_url', 'verified_on']);
        $service->update($this->validateService($request, $service));
        $after = $service->only(array_keys($before));
        ActivityLog::logAction($request->user()->id, 'update_office_service', 'Service modifié (bureau ' . $service->office_id . ')', OfficeService::class, $service->id, $before, $after);

        return response()->json(['data' => $this->service($service->fresh()->load(['office.city', 'demarche:id,slug']))]);
    }

    public function destroyService(Request $request, OfficeService $service): JsonResponse
    {
        ActivityLog::logAction($request->user()->id, 'delete_office_service', "Retrait du service {$service->status} (bureau {$service->office_id}, démarche {$service->demarche_id})", OfficeService::class, $service->id);
        $service->delete();

        return response()->json(['message' => 'Service retiré.']);
    }

    private function validateUnit(Request $request, ?GeoUnit $unit = null): array
    {
        $data = $request->validate([
            'level' => [$unit ? 'prohibited' : 'required', Rule::in(GeoUnit::LEVELS)],
            'parent_id' => ['nullable', 'integer', 'exists:geo_units,id'],
            'name' => ['required', 'string', 'max:120'],
            'name_ar' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(GeoUnit::STATUSES)],
            'source_label' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'verified_on' => ['nullable', 'date_format:Y-m-d'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! $unit) {
            $this->checkParent($data['level'], $data['parent_id'] ?? null);
        }
        // Une unité officielle doit être sourcée et datée.
        if ($data['status'] === 'official' && (blank($data['source_url'] ?? null) || blank($data['verified_on'] ?? null))) {
            throw ValidationException::withMessages(['status' => ['Une unité officielle exige une source et une date de vérification.']]);
        }

        return $data;
    }

    private function validateService(Request $request, ?OfficeService $service = null): array
    {
        $data = $request->validate([
            'office_id' => [$service ? 'prohibited' : 'required', 'integer', 'exists:offices,id'],
            'demarche_id' => [$service ? 'prohibited' : 'required', 'integer', 'exists:demarches,id'],
            'status' => ['required', Rule::in(OfficeService::STATUSES)],
            'source_label' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'verified_on' => ['nullable', 'date_format:Y-m-d'],
        ]);

        // Sans source, aucun service ne peut être présenté comme confirmé ou signalé par la presse.
        if (in_array($data['status'], ['confirmed', 'press_reported'], true) && blank($data['source_url'] ?? null)) {
            throw ValidationException::withMessages(['source_url' => ['Une source est obligatoire pour un service confirmé ou signalé.']]);
        }
        if ($data['status'] === 'confirmed' && blank($data['verified_on'] ?? null)) {
            throw ValidationException::withMessages(['verified_on' => ['Indiquez la date de vérification d’un service confirmé.']]);
        }

        return $data;
    }

    private function checkParent(string $level, ?int $parentId): void
    {
        $allowed = self::PARENT_LEVEL[$level];
        if ($allowed === null) {
            if ($parentId !== null) {
                throw ValidationException::withMessages(['parent_id' => ['Une province n’a pas de parent.']]);
            }

            return;
        }
        $parent = $parentId ? GeoUnit::find($parentId) : null;
        if ($parent === null || ! in_array($parent->level, $allowed, true)) {
            throw ValidationException::withMessages(['parent_id' => ['Parent absent ou de niveau incompatible.']]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'unite';
        $slug = $base;
        for ($i = 2; GeoUnit::where('slug', $slug)->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    private function unit(GeoUnit $u): array
    {
        return $u->only(['id', 'parent_id', 'level', 'name', 'name_ar', 'slug', 'status', 'source_label', 'source_url', 'verified_on', 'is_active'])
            + ['path' => $u->path()];
    }

    private function service(OfficeService $s): array
    {
        return [
            'id' => $s->id,
            'office_id' => $s->office_id,
            'office' => $s->office?->name,
            'city' => $s->office?->city?->slug,
            'demarche_id' => $s->demarche_id,
            'demarche' => $s->demarche?->slug,
            'status' => $s->status,
            'source_label' => $s->source_label,
            'source_url' => $s->source_url,
            'verified_on' => $s->verified_on?->toDateString(),
        ];
    }
}
