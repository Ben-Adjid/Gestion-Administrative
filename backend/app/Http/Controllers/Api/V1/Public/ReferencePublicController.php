<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OfficeResource;
use App\Models\CalendarEvent;
use App\Models\City;
use App\Models\Demarche;
use App\Models\DocumentValidityRule;
use App\Models\LifeEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Données publiques : villes et bureaux, parcours de vie, calendrier administratif.
 * ?lang=en|ar renvoie les textes traduits (repli sur le français).
 */
class ReferencePublicController extends Controller
{
    /**
     * Lister les villes
     *
     * Villes actives avec le nombre de bureaux administratifs enregistrés dans chacune.
     *
     * @group Villes et bureaux
     * @unauthenticated
     * @queryParam lang string Langue des textes : fr (par défaut), en ou ar. Example: fr
     */
    public function cities(): JsonResponse
    {
        $language = City::requestLanguage();
        $cities = City::active()->withCount(['offices' => fn ($q) => $q->where('is_active', true)])->get();

        return response()->json(['data' => $cities->map(fn (City $city) => [
            'id' => $city->id,
            'slug' => $city->slug,
            'name' => $city->localized('name', $language),
            'province' => $city->province,
            'offices_count' => $city->offices_count,
        ])]);
    }

    /**
     * Consulter une ville
     *
     * La ville, ses bureaux (adresse, horaires, contacts vérifiés, source) et les démarches qu’on peut y faire.
     *
     * @group Villes et bureaux
     * @unauthenticated
     * @urlParam slug string required Identifiant de la ville. Example: ndjamena
     * @queryParam lang string Langue des textes : fr (par défaut), en ou ar. Example: fr
     */
    public function city(string $slug): JsonResponse
    {
        $language = City::requestLanguage();
        $city = City::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $offices = $city->offices()->where('is_active', true)->with(['city', 'demarches' => fn ($q) => $q->published()])->orderBy('name')->get();

        return response()->json(['data' => [
            'id' => $city->id,
            'slug' => $city->slug,
            'name' => $city->localized('name', $language),
            'province' => $city->province,
            'offices' => $offices->map(fn ($office) => (new OfficeResource($office))->resolve() + [
                'demarches' => $office->demarches->map(fn (Demarche $d) => [
                    'slug' => $d->slug,
                    'title' => $d->getTranslatedContent($language)['title'],
                ])->values(),
            ])->values(),
        ]]);
    }

    /**
     * Lister les parcours de vie
     *
     * Situations de vie (naissance, mariage, voyage…) regroupant plusieurs démarches.
     *
     * @group Parcours de vie
     * @unauthenticated
     * @queryParam lang string Langue des textes : fr (par défaut), en ou ar. Example: fr
     */
    public function lifeEvents(): JsonResponse
    {
        $language = LifeEvent::requestLanguage();
        $events = LifeEvent::active()->withCount(['demarches' => fn ($q) => $q->published()])->get();

        return response()->json(['data' => $events->filter(fn ($e) => $e->demarches_count > 0)->map(fn (LifeEvent $event) => [
            'slug' => $event->slug,
            'title' => $event->localized('title', $language),
            'description' => $event->localized('description', $language),
            'icon' => $event->icon,
            'steps_count' => $event->demarches_count,
        ])->values()]);
    }

    /**
     * Consulter un parcours de vie
     *
     * Les démarches du parcours dans l’ordre et le coût total à prévoir.
     *
     * @group Parcours de vie
     * @unauthenticated
     * @urlParam slug string required Identifiant du parcours. Example: voyager-a-l-etranger
     * @queryParam city string Slug d’une ville : ajoute les bureaux de cette ville à chaque étape. Example: ndjamena
     * @queryParam lang string Langue des textes : fr (par défaut), en ou ar. Example: fr
     */
    public function lifeEvent(string $slug, Request $request): JsonResponse
    {
        $language = LifeEvent::requestLanguage();
        $event = LifeEvent::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $citySlug = $request->query('city');

        $demarches = $event->demarches()->published()
            ->with(['offices' => fn ($q) => $q->where('is_active', true)
                ->when($citySlug, fn ($q) => $q->whereHas('city', fn ($c) => $c->where('slug', $citySlug)))
                ->with('city')])
            ->get();

        $steps = $demarches->values()->map(function (Demarche $demarche, int $index) use ($language) {
            $content = $demarche->getTranslatedContent($language);

            return [
                'position' => $index + 1,
                'slug' => $demarche->slug,
                'title' => $content['title'],
                'summary' => $content['summary'],
                'cost' => $content['cost'],
                'processing_time' => $content['processing_time'],
                'required_documents' => $content['required_documents'] ?? [],
                'location' => $content['location'],
                'cost_estimate' => $demarche->costEstimate(),
                'offices' => OfficeResource::collection($demarche->offices)->resolve(),
            ];
        });

        $estimates = $steps->pluck('cost_estimate');

        return response()->json(['data' => [
            'slug' => $event->slug,
            'title' => $event->localized('title', $language),
            'description' => $event->localized('description', $language),
            'icon' => $event->icon,
            'steps' => $steps,
            'total' => [
                'amount' => $estimates->sum('total'),
                // Faux si au moins une étape a un prix non chiffrable (fourchette, « variable »).
                'complete' => $estimates->every(fn ($e) => $e['complete']),
            ],
        ]]);
    }

    /**
     * Calendrier administratif
     *
     * Dates publiées en cours ou à venir. Chaque date indique son type (kind), son statut (ongoing ou upcoming)
     * et le nombre de jours restants (days_left : jusqu’à la fin d’une période en cours, sinon jusqu’au début).
     *
     * @group Calendrier
     * @unauthenticated
     * @queryParam category string Domaine : elections, education, exams, identity, tax, other. Example: exams
     * @queryParam kind string Type d’échéance : period (période d’inscription…), deadline (date limite), event (date unique). Example: deadline
     * @queryParam lang string Langue des textes : fr (par défaut), en ou ar. Example: fr
     */
    public function calendar(Request $request): JsonResponse
    {
        $language = CalendarEvent::requestLanguage();
        $category = $request->query('category');
        $kind = $request->query('kind');

        $events = CalendarEvent::upcoming()->with('demarche')
            ->when(in_array($category, CalendarEvent::CATEGORIES, true), fn ($q) => $q->where('category', $category))
            ->when(in_array($kind, CalendarEvent::KINDS, true), fn ($q) => $q->where('kind', $kind))
            ->limit(100)->get();

        return response()->json(['data' => $events->map(fn (CalendarEvent $event) => $this->presentEvent($event, $language))]);
    }

    /**
     * Validité des documents
     *
     * Durée de validité de chaque document tchadien, point de départ, conditions de renouvellement, source et date de
     * vérification. `status` : confirmed (source officielle primaire), secondary (source officielle secondaire, datée)
     * ou to_verify (aucune source : aucune durée n'est donnée). `can_compute_expiry` indique si une date d'expiration
     * peut être calculée à partir de la date de délivrance.
     *
     * @group Calendrier
     * @unauthenticated
     * @queryParam lang string Langue des textes : fr (par défaut), en ou ar. Example: fr
     */
    public function documentValidity(): JsonResponse
    {
        $language = DocumentValidityRule::requestLanguage();

        $rules = DocumentValidityRule::with('demarche:id,slug,status')->where('is_active', true)->orderBy('position')->orderBy('id')->get()
            ->map(fn (DocumentValidityRule $rule) => [
                // L'identifiant sert au formulaire « mes documents » pour rattacher un document à sa règle.
                'id' => $rule->id,
                'key' => $rule->key,
                'name' => $rule->localized('name', $language),
                'country_code' => $rule->country_code,
                'category' => $rule->category,
                'validity_months' => $rule->validity_months,
                'validity_start' => $rule->validity_start,
                'can_compute_expiry' => $rule->canComputeExpiry(),
                'renewal' => $rule->localized('renewal', $language),
                'notes' => $rule->localized('notes', $language),
                'status' => $rule->status,
                'source_label' => $rule->source_label,
                'source_url' => $rule->source_url,
                'source_date' => $rule->source_date?->format('Y-m-d'),
                'verified_on' => $rule->verified_on?->format('Y-m-d'),
                // Lien vers la fiche seulement si elle est publiée (jamais vers un brouillon).
                'demarche_slug' => $rule->demarche?->status === 'published' ? $rule->demarche->slug : null,
            ]);

        return response()->json(['data' => $rules]);
    }

    /**
     * Ajouter une date à son agenda
     *
     * Fichier iCalendar (.ics). Une date limite reçoit un rappel 3 jours avant.
     *
     * @group Calendrier
     * @unauthenticated
     * @urlParam id integer required Identifiant de la date publiée. Example: 1
     * @queryParam lang string Langue des textes : fr (par défaut), en ou ar. Example: fr
     * @response 200 scenario="Fichier .ics" BEGIN:VCALENDAR
     * @response 404 scenario="Date inconnue ou non publiée" {"message": "Ressource introuvable."}
     */
    public function calendarIcs(int $id): Response
    {
        $event = CalendarEvent::where('is_published', true)->findOrFail($id);
        $language = CalendarEvent::requestLanguage();
        $escape = fn (?string $text) => str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], (string) $text);
        $end = ($event->ends_on ?? $event->starts_on)->copy()->addDay();
        $url = config('frontend.url') . '/calendrier';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//DemarchesTchad//Calendrier//FR',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:calendar-' . $event->id . '@demarchestchad',
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:' . $event->starts_on->format('Ymd'),
            'DTEND;VALUE=DATE:' . $end->format('Ymd'),
            'SUMMARY:' . $escape(($event->kind === 'deadline' ? self::DEADLINE_PREFIX[$language] : '') . $event->localized('title', $language)),
            'DESCRIPTION:' . $escape(trim(($event->localized('description', $language) ?? '') . "\n" . $url)),
            'URL:' . $url,
            // Date limite : rappel automatique 3 jours avant dans l'agenda du téléphone.
            ...($event->kind === 'deadline' ? ['BEGIN:VALARM', 'ACTION:DISPLAY', 'TRIGGER:-P3D', 'DESCRIPTION:' . $escape($event->localized('title', $language)), 'END:VALARM'] : []),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines) . "\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="demarchestchad-' . $event->id . '.ics"',
        ]);
    }

    private const DEADLINE_PREFIX = ['fr' => 'Date limite : ', 'en' => 'Deadline: ', 'ar' => 'آخر أجل: '];

    private function presentEvent(CalendarEvent $event, string $language): array
    {
        $today = now()->startOfDay();
        $ongoing = $event->kind === 'period' && $event->starts_on->lte($today);
        // Jours restants : jusqu'à la fin pour une période en cours, sinon jusqu'à la date (début, limite ou événement).
        $target = $ongoing ? $event->ends_on : $event->starts_on;

        return [
            'id' => $event->id,
            'title' => $event->localized('title', $language),
            'description' => $event->localized('description', $language),
            'category' => $event->category,
            'kind' => $event->kind,
            'status' => $ongoing ? 'ongoing' : 'upcoming',
            'days_left' => (int) $today->diffInDays($target->copy()->startOfDay(), false),
            'starts_on' => $event->starts_on->toDateString(),
            'ends_on' => $event->ends_on?->toDateString(),
            'source_url' => $event->source_url,
            'demarche' => $event->demarche && $event->demarche->status === 'published' ? [
                'slug' => $event->demarche->slug,
                'title' => $event->demarche->getTranslatedContent($language)['title'],
            ] : null,
        ];
    }
}
