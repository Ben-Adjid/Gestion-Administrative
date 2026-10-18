<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OfficeResource;
use App\Models\ActivityLog;
use App\Models\CalendarEvent;
use App\Models\City;
use App\Models\DocumentValidityRule;
use App\Models\LifeEvent;
use App\Models\Office;
use App\Models\Synonyme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Données de référence gérées par les modérateurs (admin, super_admin) :
 * villes, bureaux, représentations à l'étranger, parcours de vie et calendrier.
 * Chaque modification est inscrite au journal d'audit.
 */
class AdminReferenceController extends Controller
{
    // ---------------------------------------------------------------- Villes

    public function cities(): JsonResponse
    {
        return response()->json(['data' => City::withCount('offices')->orderBy('display_order')->orderBy('name')->get()]);
    }

    public function storeCity(Request $request): JsonResponse
    {
        $data = $this->validateCity($request);
        $city = City::create($data + ['slug' => $this->uniqueSlug(City::class, $data['slug'] ?? $data['name'])]);
        $this->log($request, 'create_city', "Création de la ville « {$city->name} »", $city);

        return response()->json(['data' => $city], 201);
    }

    public function updateCity(Request $request, City $city): JsonResponse
    {
        $data = $this->validateCity($request, $city);
        $city->update($data);
        $this->log($request, 'update_city', "Modification de la ville « {$city->name} »", $city);

        return response()->json(['data' => $city]);
    }

    public function destroyCity(Request $request, City $city): JsonResponse
    {
        if ($city->offices()->exists()) {
            return response()->json(['message' => __('messages.city_has_offices')], 409);
        }
        $this->log($request, 'delete_city', "Suppression de la ville « {$city->name} »", $city);
        $city->delete();

        return response()->json(null, 204);
    }

    private function validateCity(Request $request, ?City $city = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_ar' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'alpha_dash', 'max:100', Rule::unique('cities', 'slug')->ignore($city?->id)],
            'province' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
    }

    // ---------------------------------------------------------------- Bureaux

    public function offices(Request $request): JsonResponse
    {
        $offices = Office::with(['city', 'demarches'])
            ->when($request->query('city_id'), fn ($q, $cityId) => $q->where('city_id', $cityId))
            ->orderBy('name')->get();

        return response()->json(['data' => OfficeResource::collection($offices)->resolve()]);
    }

    public function storeOffice(Request $request): JsonResponse
    {
        $data = $this->validateOffice($request);
        $office = Office::create($data);
        $office->demarches()->sync($data['demarche_ids'] ?? []);
        $this->log($request, 'create_office', "Création du bureau « {$office->name} »", $office);

        return response()->json(['data' => (new OfficeResource($office->load(['city', 'demarches'])))->resolve()], 201);
    }

    public function updateOffice(Request $request, Office $office): JsonResponse
    {
        $data = $this->validateOffice($request);
        $office->update($data);
        $office->demarches()->sync($data['demarche_ids'] ?? []);
        $this->log($request, 'update_office', "Modification du bureau « {$office->name} »", $office);

        return response()->json(['data' => (new OfficeResource($office->load(['city', 'demarches'])))->resolve()]);
    }

    public function destroyOffice(Request $request, Office $office): JsonResponse
    {
        $this->log($request, 'delete_office', "Suppression du bureau « {$office->name} »", $office);
        $office->delete();

        return response()->json(null, 204);
    }

    private function validateOffice(Request $request): array
    {
        return $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'name' => ['required', 'string', 'max:200'],
            'name_en' => ['nullable', 'string', 'max:200'],
            'name_ar' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_ar' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:150'],
            'opening_hours_en' => ['nullable', 'string', 'max:150'],
            'opening_hours_ar' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:7,24', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:13,24.5', 'required_with:latitude'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
            'demarche_ids' => ['nullable', 'array'],
            'demarche_ids.*' => ['integer', 'exists:demarches,id'],
        ]);
    }

    // ---------------------------------------------------------------- Parcours de vie

    public function lifeEvents(): JsonResponse
    {
        $events = LifeEvent::with('demarches:id,title,slug,status')->orderBy('display_order')->orderBy('title')->get();

        return response()->json(['data' => $events->map(fn (LifeEvent $event) => $event->toArray() + [
            'demarche_ids' => $event->demarches->pluck('id')->all(),
        ])]);
    }

    public function storeLifeEvent(Request $request): JsonResponse
    {
        $data = $this->validateLifeEvent($request);
        $event = LifeEvent::create(collect($data)->except('demarche_ids')->all() + [
            'slug' => $this->uniqueSlug(LifeEvent::class, $data['slug'] ?? $data['title']),
        ]);
        $this->syncSteps($event, $data['demarche_ids']);
        $this->log($request, 'create_life_event', "Création du parcours « {$event->title} »", $event);

        return response()->json(['data' => $event->load('demarches')], 201);
    }

    public function updateLifeEvent(Request $request, LifeEvent $lifeEvent): JsonResponse
    {
        $data = $this->validateLifeEvent($request, $lifeEvent);
        $lifeEvent->update(collect($data)->except('demarche_ids')->all());
        $this->syncSteps($lifeEvent, $data['demarche_ids']);
        $this->log($request, 'update_life_event', "Modification du parcours « {$lifeEvent->title} »", $lifeEvent);

        return response()->json(['data' => $lifeEvent->load('demarches')]);
    }

    public function destroyLifeEvent(Request $request, LifeEvent $lifeEvent): JsonResponse
    {
        $this->log($request, 'delete_life_event', "Suppression du parcours « {$lifeEvent->title} »", $lifeEvent);
        $lifeEvent->delete();

        return response()->json(null, 204);
    }

    private function validateLifeEvent(Request $request, ?LifeEvent $event = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'title_en' => ['nullable', 'string', 'max:150'],
            'title_ar' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'slug' => ['nullable', 'alpha_dash', 'max:100', Rule::unique('life_events', 'slug')->ignore($event?->id)],
            'icon' => ['nullable', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
            // Ordre des étapes = ordre de la liste
            'demarche_ids' => ['required', 'array', 'min:1', 'max:20'],
            'demarche_ids.*' => ['integer', 'distinct', 'exists:demarches,id'],
        ]);
    }

    private function syncSteps(LifeEvent $event, array $demarcheIds): void
    {
        $event->demarches()->sync(collect($demarcheIds)->values()->mapWithKeys(fn ($id, $index) => [$id => ['position' => $index + 1]])->all());
    }

    // ---------------------------------------------------------------- Calendrier

    public function calendar(): JsonResponse
    {
        return response()->json(['data' => CalendarEvent::with('demarche:id,title,slug')->orderByDesc('starts_on')->get()]);
    }

    public function storeCalendarEvent(Request $request): JsonResponse
    {
        $event = CalendarEvent::create($this->validateCalendarEvent($request));
        $this->log($request, 'create_calendar_event', "Ajout au calendrier : « {$event->title} »", $event);

        return response()->json(['data' => $event], 201);
    }

    public function updateCalendarEvent(Request $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $calendarEvent->update($this->validateCalendarEvent($request));
        $this->log($request, 'update_calendar_event', "Modification du calendrier : « {$calendarEvent->title} »", $calendarEvent);

        return response()->json(['data' => $calendarEvent]);
    }

    public function destroyCalendarEvent(Request $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $this->log($request, 'delete_calendar_event', "Suppression du calendrier : « {$calendarEvent->title} »", $calendarEvent);
        $calendarEvent->delete();

        return response()->json(null, 204);
    }

    private function validateCalendarEvent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'title_en' => ['nullable', 'string', 'max:200'],
            'title_ar' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in(CalendarEvent::CATEGORIES)],
            'kind' => ['required', Rule::in(CalendarEvent::KINDS)],
            'starts_on' => ['required', 'date'],
            // Une période a obligatoirement une fin ; une date limite ou un événement n'en ont pas.
            'ends_on' => ['nullable', 'date', 'required_if:kind,period', 'prohibited_unless:kind,period', 'after:starts_on'],
            'demarche_id' => ['nullable', 'integer', 'exists:demarches,id'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'is_published' => ['boolean'],
        ]);
    }

    // ---------------------------------------------------------------- Validité des documents

    public function documentValidityRules(): JsonResponse
    {
        return response()->json(['data' => DocumentValidityRule::orderBy('position')->orderBy('id')->get()]);
    }

    public function storeDocumentValidityRule(Request $request): JsonResponse
    {
        $rule = DocumentValidityRule::create($this->validateDocumentValidityRule($request));
        $this->log($request, 'create_document_validity', "Ajout de la règle de validité « {$rule->name} »", $rule);

        return response()->json(['data' => $rule], 201);
    }

    public function updateDocumentValidityRule(Request $request, DocumentValidityRule $rule): JsonResponse
    {
        $rule->update($this->validateDocumentValidityRule($request, $rule));
        $this->log($request, 'update_document_validity', "Modification de la règle de validité « {$rule->name} »", $rule);

        return response()->json(['data' => $rule]);
    }

    public function destroyDocumentValidityRule(Request $request, DocumentValidityRule $rule): JsonResponse
    {
        $this->log($request, 'delete_document_validity', "Suppression de la règle de validité « {$rule->name} »", $rule);
        $rule->delete();

        return response()->json(null, 204);
    }

    /**
     * Aucune durée sans source : une durée exige une source (URL) et un statut autre que « à vérifier » ;
     * une règle « à vérifier » ne porte aucune durée (sinon le site calculerait une fausse date d'expiration).
     */
    private function validateDocumentValidityRule(Request $request, ?DocumentValidityRule $rule = null): array
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', Rule::unique('document_validity_rules', 'key')->ignore($rule?->id)],
            'name' => ['required', 'string', 'max:150'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'name_ar' => ['nullable', 'string', 'max:150'],
            'category' => ['required', Rule::in(DocumentValidityRule::CATEGORIES)],
            'demarche_id' => ['nullable', 'integer', 'exists:demarches,id'],
            'validity_months' => ['nullable', 'integer', 'min:1', 'max:600', 'prohibited_if:status,to_verify'],
            'validity_start' => ['required', Rule::in(DocumentValidityRule::STARTS)],
            'renewal' => ['nullable', 'string', 'max:1000'],
            'renewal_en' => ['nullable', 'string', 'max:1000'],
            'renewal_ar' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'notes_en' => ['nullable', 'string', 'max:1000'],
            'notes_ar' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(DocumentValidityRule::STATUSES)],
            'source_label' => ['nullable', 'string', 'max:255', 'required_unless:status,to_verify'],
            'source_url' => ['nullable', 'url', 'max:255', 'required_unless:status,to_verify'],
            'source_date' => ['nullable', 'date'],
            'verified_on' => ['nullable', 'date', 'before_or_equal:today'],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
        ]);
        $validated['position'] ??= 0;

        return $validated;
    }

    // ---------------------------------------------------------------- Synonymes de recherche

    public function synonyms(): JsonResponse
    {
        return response()->json(['data' => Synonyme::orderBy('term')->get(['id', 'term', 'synonym', 'is_active'])]);
    }

    public function storeSynonym(Request $request): JsonResponse
    {
        $synonym = Synonyme::create($this->validateSynonym($request) + ['weight' => 5]);
        $this->log($request, 'create_synonym', "Synonyme ajouté : « {$synonym->term} » ↔ « {$synonym->synonym} »", $synonym);

        return response()->json(['data' => $synonym], 201);
    }

    public function updateSynonym(Request $request, Synonyme $synonym): JsonResponse
    {
        $synonym->update($this->validateSynonym($request, $synonym));
        $this->log($request, 'update_synonym', "Synonyme modifié : « {$synonym->term} » ↔ « {$synonym->synonym} »", $synonym);

        return response()->json(['data' => $synonym]);
    }

    public function destroySynonym(Request $request, Synonyme $synonym): JsonResponse
    {
        $this->log($request, 'delete_synonym', "Synonyme supprimé : « {$synonym->term} » ↔ « {$synonym->synonym} »", $synonym);
        $synonym->delete();

        return response()->json(null, 204);
    }

    private function validateSynonym(Request $request, ?Synonyme $synonym = null): array
    {
        $data = $request->validate([
            'term' => ['required', 'string', 'max:100', 'different:synonym'],
            'synonym' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);
        $data['term'] = mb_strtolower(trim($data['term']));
        $data['synonym'] = mb_strtolower(trim($data['synonym']));

        $duplicate = Synonyme::where('term', $data['term'])->where('synonym', $data['synonym'])
            ->when($synonym, fn ($q) => $q->where('id', '!=', $synonym->id))->exists();
        if ($duplicate) {
            throw \Illuminate\Validation\ValidationException::withMessages(['term' => [__('validation.unique', ['attribute' => __('validation.attributes.term')])]]);
        }

        return $data;
    }

    // ---------------------------------------------------------------- Outils

    private function uniqueSlug(string $modelClass, string $source): string
    {
        $base = Str::slug($source) ?: 'element';
        $slug = $base;
        for ($i = 2; $modelClass::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    private function log(Request $request, string $action, string $description, Model $subject): void
    {
        ActivityLog::logAction($request->user()->id, $action, $description, $subject::class, $subject->getKey());
    }
}
