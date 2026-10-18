<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Demarche;
use App\Models\GeoUnit;
use App\Models\Office;
use App\Models\OfficeService;
use Illuminate\Console\Command;

/**
 * Rapport de couverture : ce qui est recensé, ce qui est attesté par une source, et ce qui reste à vérifier.
 * Aucun chiffre n'est arrondi vers le « complet » : une lacune apparaît comme une lacune.
 */
class GeoCoverageReport extends Command
{
    protected $signature = 'geo:coverage-report';

    protected $description = 'Rapport de couverture géographique et des bureaux (ce qui est vérifié, ce qui reste à vérifier)';

    public function handle(): int
    {
        $published = Demarche::where('status', 'published')->count();
        $displayable = OfficeService::whereIn('status', OfficeService::DISPLAYABLE)->get();
        $documentedDemarches = $displayable->pluck('demarche_id')->unique()->count();
        $localitiesWithOffice = $displayable->map(fn (OfficeService $s) => $s->office?->city_id)->filter()->unique()->count();

        $this->table(['Indicateur', 'Valeur'], [
            ['Localités recensées (table cities)', City::count()],
            ['Localités du référentiel (geo_units, niveau localité)', GeoUnit::where('level', 'locality')->count()],
            ['Provinces recensées', GeoUnit::where('level', 'province')->count()],
            ['Départements recensés', GeoUnit::where('level', 'department')->count()],
            ['Communes recensées', GeoUnit::where('level', 'commune')->count()],
            ['Unités officielles (source officielle datée)', GeoUnit::where('status', 'official')->count()],
            ['Unités à vérifier', GeoUnit::where('status', 'to_verify')->count()],
            ['Bureaux enregistrés', Office::count()],
            ['Bureaux actifs avec source', Office::where('is_active', true)->whereNotNull('source_url')->count()],
            ['Services confirmés (source officielle)', OfficeService::where('status', 'confirmed')->count()],
            ['Services signalés par la presse', OfficeService::where('status', 'press_reported')->count()],
            ['Services à vérifier (non affichés)', OfficeService::where('status', 'to_verify')->count()],
            ['Démarches publiées', $published],
            ['Démarches avec au moins un bureau attesté', $documentedDemarches],
            ['Localités avec au moins un bureau attesté', $localitiesWithOffice],
            ['Localités sans aucun bureau attesté', max(0, City::count() - $localitiesWithOffice)],
        ]);

        $this->warn('Lacunes : ce rapport ne prouve pas la couverture complète du Tchad. Les départements et communes ne sont pas encore importés.');

        return self::SUCCESS;
    }
}
