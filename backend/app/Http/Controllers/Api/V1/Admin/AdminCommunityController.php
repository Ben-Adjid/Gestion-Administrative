<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Mail\QuestionAnsweredMail;
use App\Models\ActivityLog;
use App\Models\Answer;
use App\Models\DemarcheReview;
use App\Models\Question;
use App\Models\SearchMiss;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Modération des contributions du public (avis, questions, réponses) et suivi des recherches sans résultat.
 * L'auteur n'est jamais identifié autrement que par le nom qu'il a choisi d'indiquer.
 */
class AdminCommunityController extends Controller
{
    // ---------------------------------------------------------------- Avis

    public function reviews(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');
        $reviews = DemarcheReview::with(['demarche:id,title,slug', 'city:id,name'])
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->latest()->paginate(30);

        return response()->json($reviews);
    }

    public function moderateReview(Request $request, DemarcheReview $review): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $review->update($data + ['moderated_by' => $request->user()->id, 'moderated_at' => now()]);
        $this->log($request, 'moderate_review', "Avis n°{$review->id} : {$data['status']}", DemarcheReview::class, $review->id);

        return response()->json(['data' => $review]);
    }

    public function destroyReview(Request $request, DemarcheReview $review): JsonResponse
    {
        $this->log($request, 'delete_review', "Suppression de l'avis n°{$review->id}", DemarcheReview::class, $review->id);
        $review->delete();

        return response()->json(null, 204);
    }

    // ---------------------------------------------------------------- Questions et réponses

    public function questions(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');
        $questions = Question::with(['demarche:id,title,slug', 'answers.admin:id,name'])
            ->when($status === 'pending_answers', fn ($q) => $q->whereHas('answers', fn ($a) => $a->where('status', 'pending')))
            ->when(in_array($status, ['pending', 'published', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->latest()->paginate(30);

        return response()->json($questions);
    }

    public function moderateQuestion(Request $request, Question $question): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['published', 'rejected'])]]);
        $question->update($data);
        $this->log($request, 'moderate_question', "Question n°{$question->id} : {$data['status']}", Question::class, $question->id);

        return response()->json(['data' => $question->load('answers')]);
    }

    /** Réponse officielle de l'équipe : publiée immédiatement, avec la question si elle était en attente. */
    public function answerQuestion(Request $request, Question $question): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:5', 'max:3000']]);

        $answer = $question->answers()->create([
            'body' => $data['body'],
            'admin_id' => $request->user()->id,
            'is_official' => true,
            'status' => 'published',
        ]);
        if ($question->status === 'pending') {
            $question->update(['status' => 'published']);
        }

        if (filled($question->author_email)) {
            Mail::to($question->author_email)->queue(new QuestionAnsweredMail($question, $answer));
        }

        $this->log($request, 'answer_question', "Réponse officielle à la question n°{$question->id}", Question::class, $question->id);

        return response()->json(['data' => $question->load('answers')], 201);
    }

    public function moderateAnswer(Request $request, Answer $answer): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['published', 'rejected'])]]);
        $answer->update($data);
        $this->log($request, 'moderate_answer', "Réponse n°{$answer->id} : {$data['status']}", Answer::class, $answer->id);

        return response()->json(['data' => $answer]);
    }

    public function destroyQuestion(Request $request, Question $question): JsonResponse
    {
        $this->log($request, 'delete_question', "Suppression de la question n°{$question->id}", Question::class, $question->id);
        $question->delete();

        return response()->json(null, 204);
    }

    // ---------------------------------------------------------------- Recherches sans résultat

    public function searchMisses(Request $request): JsonResponse
    {
        $misses = SearchMiss::query()
            ->when(!$request->boolean('include_dismissed'), fn ($q) => $q->whereNull('dismissed_at'))
            ->orderByDesc('count')->orderByDesc('last_searched_at')
            ->limit(200)->get();

        return response()->json(['data' => $misses]);
    }

    /** Marque un terme comme traité (fiche écrite ou recherche non pertinente). */
    public function dismissSearchMiss(Request $request, SearchMiss $searchMiss): JsonResponse
    {
        $searchMiss->update(['dismissed_at' => now()]);
        $this->log($request, 'dismiss_search_miss', "Recherche « {$searchMiss->term} » marquée comme traitée", SearchMiss::class, $searchMiss->id);

        return response()->json(['data' => $searchMiss]);
    }

    private function log(Request $request, string $action, string $description, string $type, int $id): void
    {
        ActivityLog::logAction($request->user()->id, $action, $description, $type, $id);
    }
}
