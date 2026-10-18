<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Demarche extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'demarches';

    /** Organisme compétent : service de la commune, service régional (province, juridiction) ou organisme national. */
    public const AUTHORITY_LEVELS = ['local', 'regional', 'national'];

    protected $fillable = [
        'categorie_id',
        'title',
        'title_en',
        'title_ar',
        'slug',
        'summary',
        'summary_en',
        'summary_ar',
        'description',
        'description_en',
        'description_ar',
        'required_documents',
        'required_documents_en',
        'required_documents_ar',
        'procedure_steps',
        'procedure_steps_en',
        'procedure_steps_ar',
        'conditions',
        'conditions_en',
        'conditions_ar',
        'cost',
        'cost_en',
        'cost_ar',
        'cost_amount',
        'extra_costs',
        'processing_time',
        'processing_time_en',
        'processing_time_ar',
        'processing_time_days',
        'institution',
        'institution_en',
        'institution_ar',
        'location',
        'location_en',
        'location_ar',
        'location_city_id',
        'authority_level',
        'contact_email',
        'contact_phone',
        'official_source_url',
        'last_verified_at',
        'status',
        'is_online',
        'online_url',
        'views_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'required_documents' => 'array',
        'required_documents_en' => 'array',
        'required_documents_ar' => 'array',
        'procedure_steps' => 'array',
        'procedure_steps_en' => 'array',
        'procedure_steps_ar' => 'array',
        'conditions' => 'array',
        'conditions_en' => 'array',
        'conditions_ar' => 'array',
        'cost_amount' => 'decimal:2',
        'extra_costs' => 'array',
        'processing_time_days' => 'integer',
        'is_online' => 'boolean',
        'views_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'last_verified_at' => 'date',
    ];

    /**
     * Catégorie associée
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    /**
     * Administrateur créateur
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Administrateur ayant modifié
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    /**
     * Signalements citoyens liés à cette démarche
     */
    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class, 'demarche_id');
    }

    /** Villes où cette démarche a au moins un bureau actif avec une source (adresse vérifiée). */
    public static function cityOptions(self $demarche): array
    {
        return $demarche->offices
            ->filter(fn ($o) => $o->is_active && $o->source_url && $o->city)
            ->map(fn ($o) => ['slug' => $o->city->slug, 'name' => $o->city->name])
            ->unique('slug')->values()->all();
    }

    /** Ville à laquelle se rapporte le texte libre « location » (null : valable partout). */
    public function locationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'location_city_id');
    }

    /** Bureaux (guichets) où effectuer la démarche, toutes villes confondues. */
    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class, 'demarche_office');
    }

    /** Retours d'expérience des citoyens. */
    public function reviews(): HasMany
    {
        return $this->hasMany(DemarcheReview::class);
    }

    /** Parcours de vie qui incluent cette démarche. */
    public function lifeEvents(): BelongsToMany
    {
        return $this->belongsToMany(LifeEvent::class, 'life_event_demarche')->withPivot('position');
    }

    /** Questions publiques posées sur cette démarche. */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Estimation du coût total : prix officiel + frais annexes.
     * Si le prix officiel n'est pas un montant unique (fourchette, « variable »), seule la somme
     * des frais annexes est connue : 'complete' vaut alors false.
     *
     * @return array{official: ?int, extras: int, total: int, complete: bool}
     */
    public function costEstimate(): array
    {
        $extras = collect($this->extra_costs ?? [])->sum(fn ($item) => (int) ($item['amount'] ?? 0));
        $official = $this->cost_amount !== null ? (int) $this->cost_amount : null;

        return [
            'official' => $official,
            'extras' => $extras,
            'total' => ($official ?? 0) + $extras,
            'complete' => $official !== null,
        ];
    }

    /**
     * Montant numérique déduit d'un coût affiché à prix unique (« 5 000 FCFA » → 5000, « Gratuit » → 0).
     * Retourne null pour une fourchette ou un texte libre (« 5 000 – 10 000 FCFA », « Variable »).
     */
    public static function amountFromCost(?string $cost): ?int
    {
        $cost = trim((string) $cost);
        if (preg_match('/^gratuit$/iu', $cost)) {
            return 0;
        }
        if (preg_match('/^(\d{1,3}(?:[\s\x{00A0}\x{202F},.]\d{3})*|\d+)\s*FCFA$/iu', $cost, $m)) {
            return (int) preg_replace('/\D/u', '', $m[1]);
        }
        return null;
    }

    /**
     * Scope pour les démarches publiées uniquement
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** Champs parcourus par la recherche (titre, résumé, description, institution et lieu, dans les trois langues). */
    private const SEARCH_FIELDS = [
        'title', 'title_en', 'title_ar', 'summary', 'summary_en', 'summary_ar', 'description',
        'institution', 'institution_en', 'institution_ar', 'location', 'location_en', 'location_ar',
    ];
    private const TITLE_FIELDS = ['title', 'title_en', 'title_ar'];
    private const SUMMARY_FIELDS = ['summary', 'summary_en', 'summary_ar'];

    /**
     * Termes recherchés : le terme saisi et ses synonymes configurés dans l'administration
     * (ex. « pièce d'identité » → « carte d'identité »), dans les deux sens.
     *
     * @return string[]
     */
    public static function searchTerms(string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $synonyms = Synonyme::active()->byBothDirections($term)->get(['term', 'synonym'])
            ->flatMap(fn ($s) => [$s->term, $s->synonym]);

        return collect([$term])->merge($synonyms)
            ->map(fn ($t) => trim((string) $t))->filter()
            ->unique(fn ($t) => mb_strtolower($t))->take(6)->values()->all();
    }

    /** Escape des jokers LIKE saisis par l'utilisateur (« % » et « _ » sont cherchés tels quels). */
    private static function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }

    /**
     * Recherche plein texte (cahier des charges, module 1 : titre + description + lieu), synonymes compris.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $terms = self::searchTerms((string) $term);
        if (!$terms) {
            return $query;
        }

        return $query->where(function ($q) use ($terms) {
            foreach ($terms as $t) {
                foreach (self::SEARCH_FIELDS as $field) {
                    $q->orWhere($field, 'like', self::like($t));
                }
            }
        });
    }

    /**
     * Tri par pertinence : correspondance dans le titre, puis dans le résumé, puis ailleurs ;
     * à pertinence égale, les fiches les plus consultées d'abord.
     */
    public function scopeOrderByRelevance(Builder $query, ?string $term): Builder
    {
        $terms = self::searchTerms((string) $term);
        if (!$terms) {
            return $query->orderByDesc('views_count');
        }

        $clause = fn (array $fields) => '(' . implode(' OR ', array_merge(...array_map(
            fn () => array_map(fn ($f) => "{$f} LIKE ?", $fields), $terms
        ))) . ')';
        $bindings = fn (array $fields) => array_merge(...array_map(fn ($t) => array_fill(0, count($fields), self::like($t)), $terms));

        return $query
            ->orderByRaw('CASE WHEN ' . $clause(self::TITLE_FIELDS) . ' THEN 0 WHEN ' . $clause(self::SUMMARY_FIELDS) . ' THEN 1 ELSE 2 END',
                array_merge($bindings(self::TITLE_FIELDS), $bindings(self::SUMMARY_FIELDS)))
            ->orderByDesc('views_count');
    }

    /**
     * Scope pour filtrer par catégorie
     */
    public function scopeOfCategory(Builder $query, $categorieId): Builder
    {
        if (empty($categorieId)) {
            return $query;
        }

        return $query->where('categorie_id', $categorieId);
    }

    /**
     * Récupère le contenu traduit de la démarche selon la langue
     * Retourne les traductions avec fallback sur le français
     */
    public const TRANSLATABLE = [
        'title', 'summary', 'description', 'cost', 'required_documents', 'procedure_steps',
        'conditions', 'institution', 'processing_time', 'location',
    ];

    /**
     * Contenu dans la langue demandée (en|ar), champ par champ avec repli sur le français
     * lorsque la traduction est vide.
     */
    public function getTranslatedContent(?string $language = 'fr'): array
    {
        $language = in_array($language, ['en', 'ar'], true) ? $language : null;
        $content = [];

        foreach (self::TRANSLATABLE as $field) {
            $translated = $language ? $this->getAttribute("{$field}_{$language}") : null;
            $content[$field] = (is_array($translated) ? count($translated) > 0 : filled($translated))
                ? $translated
                : $this->getAttribute($field);
        }

        return $content;
    }

    public function hasTranslation(string $language): bool
    {
        return filled($this->getAttribute("title_{$language}")) && filled($this->getAttribute("procedure_steps_{$language}"));
    }

    /**
     * Incrémentation atomique du nombre de vues
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
