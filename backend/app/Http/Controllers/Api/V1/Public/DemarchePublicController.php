<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\OfficeService;
use App\Models\Demarche;
use App\Models\Categorie;
use App\Models\SearchMiss;
use App\Models\SearchTerm;
use App\Http\Resources\Api\V1\DemarcheResource;
use App\Http\Resources\Api\V1\CategorieResource;
use App\Http\Resources\Api\V1\OfficeResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DemarchePublicController extends Controller
{
    /**
     * Lister les démarches
     *
     * Liste paginée des fiches publiées, avec recherche (synonymes compris), filtres et tri.
     *
     * @group Démarches
     * @unauthenticated
     * @queryParam q string Mot-clé recherché dans le titre, le résumé, la description, l’institution et le lieu (alias : search). Example: passeport
     * @queryParam categorie string Slug ou identifiant de catégorie (alias : category). Example: identite
     * @queryParam is_online boolean Seulement les démarches faisables en ligne (true) ou sur place (false). Example: false
     * @queryParam sort string Tri : relevance (par défaut avec q), views (par défaut sans q), title, recent. Example: views
     * @queryParam per_page integer Fiches par page, de 1 à 50 (15 par défaut). Example: 15
     * @queryParam page integer Numéro de page. Example: 1
     * @queryParam lang string Langue du contenu : fr (par défaut), en ou ar. Example: fr
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Demarche::published()->with(['categorie', 'locationCity']);

        // Alias du cahier des charges (§7.1) : ?category= et ?search=
        $categoryParam = $request->query('categorie', $request->query('category'));
        $searchParam = $request->query('q', $request->query('search'));

        // Filtre par catégorie (slug ou id)
        if (filled($categoryParam)) {
            $cat = (string) $categoryParam;
            if (is_numeric($cat)) {
                $query->where('categorie_id', (int) $cat);
            } else {
                $query->whereHas('categorie', function ($q) use ($cat) {
                    $q->where('slug', $cat);
                });
            }
        }

        // Filtre démarche en ligne
        if ($request->has('is_online')) {
            $query->where('is_online', filter_var($request->query('is_online'), FILTER_VALIDATE_BOOLEAN));
        }

        // Recherche par mot-clé
        if (filled($searchParam)) {
            $query->search((string) $searchParam);
        }

        // Tri (cahier des charges : pertinence, alphabétique, date de mise à jour ; + popularité)
        $sort = $request->query('sort', filled($searchParam) ? 'relevance' : 'views');
        if ($sort === 'recent') {
            $query->orderByDesc('updated_at');
        } elseif ($sort === 'title') {
            $query->orderBy('title', 'asc');
        } elseif ($sort === 'relevance' && filled($searchParam)) {
            $query->orderByRelevance((string) $searchParam);
        } else {
            $query->orderBy('views_count', 'desc');
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $demarches = $query->paginate($perPage);

        // Statistiques de recherche (première page seulement, terme seul) : termes les plus recherchés,
        // et recherches sans résultat pour savoir quelles fiches écrire.
        if (filled($searchParam) && $demarches->currentPage() === 1) {
            SearchTerm::record((string) $searchParam, $demarches->total());
            if ($demarches->total() === 0 && blank($categoryParam)) {
                SearchMiss::record((string) $searchParam);
            }
        }

        return DemarcheResource::collection($demarches);
    }

    /**
     * Consulter une démarche
     *
     * Fiche complète : pièces, conditions, étapes, coût, délai, institution et bureaux où l’effectuer (avec leur ville).
     *
     * @group Démarches
     * @unauthenticated
     * @urlParam slug string required Identifiant de la fiche. Example: passeport-biometrique
     * @queryParam lang string Langue du contenu : fr (par défaut), en ou ar. Example: fr
     */
    public function show(string $slug, Request $request): JsonResponse
    {
        $demarche = Demarche::published()
            ->with(['categorie', 'locationCity', 'offices' => fn ($q) => $q->where('is_active', true)->with('city')->orderBy('name')])
            ->where('slug', $slug)
            ->firstOrFail();

        // La lecture ne compte pas de vue : la page est pré-rendue (ISR) côté serveur,
        // le navigateur du visiteur signale la consultation via POST /demarches/{slug}/view.

        // Lieu compétent pour la ville demandée (?city=slug) : calculé ici, pour que site et API donnent le même résultat.
        $payload = (new DemarcheResource($demarche))->response($request)->getData(true);
        $payload['place'] = $this->placeForCity($demarche, $request->query('city'), (string) $request->query('lang', 'fr'));

        return response()->json($payload);
    }

    /**
     * Lieu compétent pour une localité donnée. Règles :
     *  - un bureau n'est proposé que s'il porte un service pour CETTE démarche, avec une source (table office_services) ;
     *  - un service « à vérifier » n'est jamais affiché comme un lieu ; un service « presse » est étiqueté comme tel ;
     *  - un bureau d'une autre localité n'est proposé qu'à titre de renseignement, compétence non confirmée ;
     *  - aucune adresse par défaut : sans donnée, on le dit.
     */
    private function placeForCity(Demarche $demarche, ?string $citySlug, string $language): array
    {
        $requested = $citySlug ? City::where('slug', $citySlug)->first() : null;

        $services = OfficeService::with(['office.city'])
            ->where('demarche_id', $demarche->id)
            ->whereIn('status', OfficeService::DISPLAYABLE)
            ->get()
            ->filter(fn (OfficeService $s) => $s->office && $s->office->is_active && $s->office->source_url && $s->office->city);

        $describe = fn (OfficeService $s) => OfficeResource::make($s->office)->resolve() + [
            'service_status' => $s->status,
            'service_source' => $s->source_label,
            'service_source_url' => $s->source_url,
            'service_verified_on' => $s->verified_on?->toDateString(),
        ];

        $local = $requested ? $services->where('office.city_id', $requested->id)->values() : collect();
        $elsewhere = $requested ? $services->where('office.city_id', '!=', $requested->id)->values()->take(3) : collect();

        // Un texte rattaché à la ville choisie est un lieu vérifié pour elle.
        $writtenPlaceApplies = filled($demarche->location)
            && $requested !== null && $demarche->location_city_id !== null && $requested->id === $demarche->location_city_id;
        // Un texte sans ville est une information générale : il est montré, mais jamais comme lieu vérifié d'une ville.
        $generalText = filled($demarche->location) && $demarche->location_city_id === null;
        $lang = in_array($language, ['en', 'ar'], true) ? $language : 'fr';
        $location = $demarche->getTranslatedContent($lang)['location'];

        return [
            'city' => $requested ? ['slug' => $requested->slug, 'name' => $requested->localized('name', $language)] : null,
            'authority_level' => $demarche->authority_level,
            'offices' => $local->map($describe)->all(),
            'elsewhere_offices' => $elsewhere->map(fn (OfficeService $s) => $describe($s) + ['competence' => 'not_confirmed'])->all(),
            'written_place' => ($writtenPlaceApplies || $generalText) ? $location : null,
            'written_place_is_general' => $generalText && ! $writtenPlaceApplies,
            'written_place_city' => $writtenPlaceApplies && $demarche->location_city_id
                ? City::find($demarche->location_city_id)?->slug : null,
            // Localités où un service est attesté pour cette démarche (sources presse ou officielles).
            'offices_available_in' => $services->map(fn (OfficeService $s) => ['slug' => $s->office->city->slug, 'name' => $s->office->city->name])
                ->unique('slug')->values()->all(),
            // Vérifié seulement avec un service officiel (« confirmed ») dans la localité, ou un lieu écrit sourcé.
            'has_verified_place' => $local->contains(fn (OfficeService $s) => $s->status === 'confirmed')
                || ($writtenPlaceApplies && filled($demarche->official_source_url)),
            'has_local_office' => $local->isNotEmpty(),
            'written_place_verified' => $writtenPlaceApplies && filled($demarche->official_source_url),
        ];
    }

    /**
     * POST /api/v1/demarches/{slug}/view — une consultation réelle par un visiteur.
     *
     * @hideFromAPIDocumentation
     */
    public function recordView(string $slug): JsonResponse
    {
        $demarche = Demarche::published()->where('slug', $slug)->firstOrFail();
        $demarche->incrementViews();

        return response()->json(null, 204);
    }

    /**
     * Lister les catégories
     *
     * Catégories actives avec le nombre de fiches publiées de chacune.
     *
     * @group Catégories
     * @unauthenticated
     * @queryParam lang string Langue des noms : fr (par défaut), en ou ar. Example: fr
     */
    public function categories(): AnonymousResourceCollection
    {
        $categories = Categorie::active()
            ->withCount(['demarches' => function ($q) {
                $q->where('status', 'published');
            }])
            ->get();

        return CategorieResource::collection($categories);
    }

    /**
     * Démarches d’une catégorie
     *
     * Fiches publiées d’une catégorie, les plus consultées d’abord ; la catégorie est jointe à la réponse.
     *
     * @group Catégories
     * @unauthenticated
     * @urlParam slug string required Identifiant de la catégorie. Example: etudes
     * @queryParam per_page integer Fiches par page, de 1 à 100 (50 par défaut). Example: 50
     * @queryParam lang string Langue du contenu : fr (par défaut), en ou ar. Example: fr
     */
    public function categoryDemarches(string $slug, Request $request): AnonymousResourceCollection
    {
        $categorie = Categorie::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $demarches = Demarche::published()
            ->where('categorie_id', $categorie->id)
            ->with('categorie')
            ->orderBy('views_count', 'desc')
            ->paginate(min(max((int) $request->query('per_page', 50), 1), 100));

        // La catégorie (nom et description dans la langue demandée) accompagne la liste.
        return DemarcheResource::collection($demarches)
            ->additional(['categorie' => new CategorieResource($categorie)]);
    }

    /**
     * Suggestions de recherche
     *
     * Jusqu’à 8 fiches correspondant au début de saisie, avec extrait et catégorie. Rien en dessous de 3 caractères.
     *
     * @group Recherche
     * @unauthenticated
     * @queryParam q string required Texte saisi (3 caractères minimum). Example: passe
     * @queryParam lang string Langue : fr (par défaut), en ou ar. Example: fr
     */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        // Cahier des charges (module 2) : suggestions à partir de 3 caractères.
        if (mb_strlen($term) < 3) {
            return response()->json(['data' => []]);
        }

        $language = in_array($request->query('lang'), ['en', 'ar'], true) ? $request->query('lang') : 'fr';

        $results = Demarche::published()
            ->with('categorie')
            ->search($term)
            ->orderByRelevance($term)
            ->limit(8)
            ->get()
            ->map(function (Demarche $d) use ($language) {
                $content = $d->getTranslatedContent($language);

                // Résultat enrichi : titre + extrait + catégorie.
                return [
                    'id' => $d->id,
                    'title' => $content['title'],
                    'slug' => $d->slug,
                    'excerpt' => \Illuminate\Support\Str::limit((string) ($content['summary'] ?: $content['description']), 110),
                    'category' => $d->categorie?->localizedName($language),
                    'institution' => $content['institution'],
                    'cost' => $content['cost'],
                    'cost_amount' => $d->cost_amount !== null ? (float) $d->cost_amount : null,
                    'processing_time' => $content['processing_time'],
                ];
            });

        return response()->json(['data' => $results]);
    }
}
