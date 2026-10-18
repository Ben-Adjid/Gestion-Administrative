<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\GeoSuggestion;
use App\Models\GeoUnit;
use App\Models\OfficeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Couverture publique : ce qui est recensé, ce qui est attesté par une source, et ce qui reste à vérifier.
 * Les localités et les bureaux sont comptés séparément : une localité recensée n'a pas forcément de bureau.
 */
class GeoCoverageController extends Controller
{
    /** GET /api/v1/geo/coverage */
    public function coverage(): JsonResponse
    {
        $localities = GeoUnit::active()->where('level', 'locality')->with('parent.parent')->orderBy('name')->get();
        $cities = City::whereIn('geo_unit_id', $localities->pluck('id'))->get()->keyBy('geo_unit_id');

        // Services affichables (« confirmed » ou « press_reported ») des bureaux actifs.
        $services = OfficeService::with('office')
            ->whereIn('status', OfficeService::DISPLAYABLE)
            ->get()
            ->filter(fn (OfficeService $s) => $s->office && $s->office->is_active);
        $servicesByCity = $services->groupBy(fn (OfficeService $s) => $s->office->city_id);

        $rows = $localities->map(function (GeoUnit $unit) use ($cities, $servicesByCity) {
            $city = $cities->get($unit->id);
            $cityServices = $city ? ($servicesByCity->get($city->id) ?? collect()) : collect();

            return [
                'slug' => $unit->slug,
                'name' => $unit->name,
                'province' => $this->provinceOf($unit)?->name,
                'status' => $unit->status,
                'offices_attested' => $cityServices->pluck('office_id')->unique()->count(),
                'demarches_documented' => $cityServices->pluck('demarche_id')->unique()->count(),
                'selectable' => $city !== null,
            ];
        })->values();

        $withOffice = $rows->where('offices_attested', '>', 0)->count();

        return response()->json([
            'data' => $rows,
            'totals' => [
                'localities' => $rows->count(),
                'localities_with_attested_office' => $withOffice,
                'localities_without_attested_office' => $rows->count() - $withOffice,
                'localities_to_verify' => $rows->where('status', 'to_verify')->count(),
                'offices_attested' => $services->pluck('office_id')->unique()->count(),
                'demarches_documented' => $services->pluck('demarche_id')->unique()->count(),
                'services_confirmed' => $services->where('status', 'confirmed')->count(),
                'services_press_reported' => $services->where('status', 'press_reported')->count(),
                'departments' => GeoUnit::where('level', 'department')->count(),
                'communes' => GeoUnit::where('level', 'commune')->count(),
                'official_units' => GeoUnit::where('status', 'official')->count(),
            ],
            'note' => 'Les départements et communes ne sont pas encore importés à partir de sources officielles.',
        ]);
    }

    /**
     * POST /api/v1/geo/suggestions : un visiteur signale une localité absente.
     * Aucune localité n'est créée ici : un administrateur la vérifie avant tout ajout.
     */
    public function suggest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:190'],
        ]);

        // Un même nom ne se signale qu'une fois en attente, pour éviter le spam.
        $exists = GeoSuggestion::where('status', 'new')->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])->exists();
        if (! $exists) {
            GeoSuggestion::create($data + ['status' => 'new', 'ip_hash' => hash('sha256', (string) $request->ip())]);
        }

        return response()->json(['message' => 'Merci. Nous vérifierons cette localité avant de l’ajouter.'], 201);
    }

    private function provinceOf(GeoUnit $unit): ?GeoUnit
    {
        $current = $unit;
        while ($current !== null && $current->level !== 'province') {
            $current = $current->parent;
        }

        return $current;
    }
}
