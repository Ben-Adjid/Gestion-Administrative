<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Concerns\LimitsPublicSubmissions;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Demarche;
use App\Models\DemarcheReview;
use App\Models\Question;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Contributions publiques sans compte : avis sur une démarche, questions et réponses.
 * Tout est publié après modération par l'équipe.
 */
class CommunityPublicController extends Controller
{
    use LimitsPublicSubmissions;

    private const MAX_REVIEWS_PER_HOUR = 5;
    private const MAX_QUESTIONS_PER_HOUR = 5;
    private const MAX_ANSWERS_PER_HOUR = 10;

    /**
     * Avis sur une démarche
     *
     * Avis publiés après modération, avec la synthèse des prix et délais réellement constatés par les usagers.
     *
     * @group Communauté
     * @unauthenticated
     * @urlParam slug string required Identifiant de la fiche. Example: passeport-biometrique
     */
    public function reviews(string $slug): JsonResponse
    {
        $demarche = Demarche::published()->where('slug', $slug)->firstOrFail();
        $approved = $demarche->reviews()->approved();

        $prices = (clone $approved)->whereNotNull('price_paid')->pluck('price_paid')->sort()->values();
        $durations = (clone $approved)->whereNotNull('duration_days')->pluck('duration_days')->sort()->values();

        $reviews = (clone $approved)->with('city')->latest('visit_date')->latest('id')->limit(30)->get();

        return response()->json([
            'summary' => [
                'count' => (clone $approved)->count(),
                'average_rating' => round((float) (clone $approved)->avg('rating'), 1),
                'median_price' => $this->median($prices),
                'median_duration_days' => $this->median($durations),
            ],
            'data' => $reviews->map(fn (DemarcheReview $review) => [
                'id' => $review->id,
                'visit_date' => $review->visit_date->toDateString(),
                'price_paid' => $review->price_paid,
                'duration_days' => $review->duration_days,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'author_name' => $review->author_name,
                'city' => $review->city?->localized('name', $this->language()),
            ]),
        ]);
    }

    /**
     * POST /demarches/{slug}/reviews
     *
     * @hideFromAPIDocumentation
     */
    public function storeReview(string $slug, Request $request): JsonResponse
    {
        $demarche = Demarche::published()->where('slug', $slug)->firstOrFail();
        if ($this->isBot($request)) {
            return response()->json(['message' => __('messages.review_received')], 201);
        }
        if ($limited = $this->submissionLimitResponse($request, 'review', self::MAX_REVIEWS_PER_HOUR)) {
            return $limited;
        }

        $data = $request->validate([
            'visit_date' => ['required', 'date', 'before_or_equal:today', 'after:' . now()->subYears(3)->toDateString()],
            'price_paid' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'duration_days' => ['nullable', 'integer', 'min:0', 'max:730'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
            'author_name' => ['nullable', 'string', 'max:100'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ]);

        $demarche->reviews()->create($data + ['status' => 'pending', 'ip_hash' => $this->ipHash($request)]);
        $this->recordSubmission($request, 'review');
        $this->alertTeam('Nouvel avis à modérer : ' . $demarche->title, "Note : {$data['rating']}/5. Un avis du public attend validation.", $data['comment'], '/admin/avis');

        return response()->json(['message' => __('messages.review_received')], 201);
    }

    /**
     * Questions et réponses
     *
     * Questions publiées et leurs réponses publiées (après modération).
     *
     * @group Communauté
     * @unauthenticated
     * @queryParam demarche string Slug d’une fiche : seulement ses questions. Example: passeport-biometrique
     * @queryParam q string Mot-clé dans les questions. Example: délai
     * @queryParam per_page integer Questions par page, de 1 à 50 (20 par défaut). Example: 20
     * @queryParam lang string Langue des textes : fr (par défaut), en ou ar. Example: fr
     */
    public function questions(Request $request): JsonResponse
    {
        $demarcheSlug = $request->query('demarche');
        $term = trim((string) $request->query('q', ''));

        $questions = Question::published()
            ->with(['demarche', 'publishedAnswers'])
            ->when($demarcheSlug, fn ($q) => $q->whereHas('demarche', fn ($d) => $d->where('slug', $demarcheSlug)))
            ->when(mb_strlen($term) >= 2, fn ($q) => $q->where('body', 'like', '%' . $term . '%'))
            ->latest()
            ->paginate(min(max((int) $request->query('per_page', 20), 1), 50));

        $language = $this->language();

        return response()->json([
            'data' => collect($questions->items())->map(fn (Question $question) => [
                'id' => $question->id,
                'body' => $question->body,
                'author_name' => $question->author_name,
                'created_at' => $question->created_at?->toIso8601String(),
                'demarche' => $question->demarche && $question->demarche->status === 'published' ? [
                    'slug' => $question->demarche->slug,
                    'title' => $question->demarche->getTranslatedContent($language)['title'],
                ] : null,
                'answers' => $question->publishedAnswers->map(fn (Answer $answer) => [
                    'id' => $answer->id,
                    'body' => $answer->body,
                    'is_official' => $answer->is_official,
                    'author_name' => $answer->is_official ? null : $answer->author_name,
                    'created_at' => $answer->created_at?->toIso8601String(),
                ])->values(),
            ]),
            'meta' => ['current_page' => $questions->currentPage(), 'last_page' => $questions->lastPage(), 'total' => $questions->total()],
        ]);
    }

    /**
     * POST /questions
     *
     * @hideFromAPIDocumentation
     */
    public function storeQuestion(Request $request): JsonResponse
    {
        if ($this->isBot($request)) {
            return response()->json(['message' => __('messages.question_received')], 201);
        }
        if ($limited = $this->submissionLimitResponse($request, 'question', self::MAX_QUESTIONS_PER_HOUR)) {
            return $limited;
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:10', 'max:1000'],
            'author_name' => ['nullable', 'string', 'max:100'],
            'author_email' => ['nullable', 'email', 'max:150'],
            'demarche_slug' => ['nullable', 'string', 'exists:demarches,slug'],
        ]);

        Question::create([
            'body' => $data['body'],
            'author_name' => $data['author_name'] ?? null,
            'author_email' => $data['author_email'] ?? null,
            'demarche_id' => isset($data['demarche_slug']) ? Demarche::published()->where('slug', $data['demarche_slug'])->value('id') : null,
            'language' => $this->language(true),
            'status' => 'pending',
            'ip_hash' => $this->ipHash($request),
        ]);
        $this->recordSubmission($request, 'question');
        $this->alertTeam('Nouvelle question à modérer', 'Une question du public attend validation.', $data['body'], '/admin/questions');

        return response()->json(['message' => __('messages.question_received')], 201);
    }

    /**
     * POST /questions/{question}/answers — réponse de la communauté (modérée).
     *
     * @hideFromAPIDocumentation
     */
    public function storeAnswer(Question $question, Request $request): JsonResponse
    {
        abort_unless($question->status === 'published', 404);
        if ($this->isBot($request)) {
            return response()->json(['message' => __('messages.answer_received')], 201);
        }
        if ($limited = $this->submissionLimitResponse($request, 'answer', self::MAX_ANSWERS_PER_HOUR)) {
            return $limited;
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:10', 'max:1500'],
            'author_name' => ['nullable', 'string', 'max:100'],
        ]);

        $question->answers()->create($data + ['is_official' => false, 'status' => 'pending', 'ip_hash' => $this->ipHash($request)]);
        $this->recordSubmission($request, 'answer');
        $this->alertTeam('Nouvelle réponse à modérer', 'Une réponse de la communauté attend validation.', $data['body'], '/admin/questions');

        return response()->json(['message' => __('messages.answer_received')], 201);
    }

    /** Alerte immédiate des modérateurs qui ont activé « contenus du public » (avis, questions, réponses). */
    private function alertTeam(string $subject, string $intro, string $content, string $adminPath): void
    {
        app(EmailService::class)->alertModerators(
            $subject,
            $intro . "\n\n" . Str::limit($content, 300) . "\n\nModérer : " . config('frontend.url') . $adminPath,
            'notify_community',
        );
    }

    /** Langue de lecture (?lang=) ou, pour un envoi, langue de l'interface (Accept-Language). */
    private function language(bool $fromHeader = false): string
    {
        $language = $fromHeader ? app()->getLocale() : request()->query('lang');

        return in_array($language, ['fr', 'en', 'ar'], true) ? $language : 'fr';
    }

    private function median($values): ?int
    {
        $count = $values->count();
        if ($count === 0) {
            return null;
        }
        $middle = intdiv($count, 2);

        return (int) round($count % 2 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2);
    }
}
