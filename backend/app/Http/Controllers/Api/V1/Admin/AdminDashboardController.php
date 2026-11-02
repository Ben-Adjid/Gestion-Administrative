<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Answer;
use App\Models\Demarche;
use App\Models\DemarcheReview;
use App\Models\Question;
use App\Models\Signalement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GET /api/v1/admin/stats — tableau de bord propre au rôle de l'administrateur connecté.
 * Chaque bloc n'est calculé que si le rôle a le droit d'en voir le contenu.
 */
class AdminDashboardController extends Controller
{
    private const VERIFICATION_MAX_AGE_DAYS = 180;

    public function show(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $data = [
            'role' => $admin->role,
            'capabilities' => $admin->capabilities(),
            'content' => $this->contentBlock(),
            'signalements' => $this->signalementsBlock($admin),
        ];

        if ($admin->role === 'editor') {
            $data['my_work'] = $this->editorBlock($admin);
        }

        if ($admin->isModerator()) {
            $data['validation_queue'] = $this->validationQueue();
            $data['community'] = $this->communityBlock();
            $data['audience'] = $this->audienceBlock();
        }

        if ($admin->isSuperAdmin()) {
            $data['team'] = $this->teamBlock();
            $data['recent_activity'] = $this->recentActivity();
        }

        return response()->json(['data' => $data]);
    }

    private function contentBlock(): array
    {
        $verifiedBefore = now()->subDays(self::VERIFICATION_MAX_AGE_DAYS);
        $staleQuery = Demarche::where('status', 'published')
            ->where(fn ($q) => $q->whereNull('last_verified_at')->orWhere('last_verified_at', '<', $verifiedBefore));

        return [
            'published' => Demarche::where('status', 'published')->count(),
            'drafts' => Demarche::where('status', 'draft')->count(),
            'archived' => Demarche::onlyTrashed()->count(),
            'needs_verification' => (clone $staleQuery)->count(),
            'verification_max_age_days' => self::VERIFICATION_MAX_AGE_DAYS,
            'to_verify' => (clone $staleQuery)
                ->orderByRaw('last_verified_at IS NOT NULL')
                ->orderBy('last_verified_at')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'last_verified_at'])
                ->map(fn (Demarche $d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'slug' => $d->slug,
                    'last_verified_at' => $d->last_verified_at?->toDateString(),
                ]),
        ];
    }

    private function signalementsBlock(Admin $admin): array
    {
        return [
            'pending' => Signalement::where('status', 'pending')->count(),
            'investigating' => Signalement::where('status', 'investigating')->count(),
            'resolved_this_month' => Signalement::where('status', 'resolved')
                ->where('resolved_at', '>=', now()->startOfMonth())->count(),
            'recent' => Signalement::with('demarche:id,title,slug')
                ->whereIn('status', ['pending', 'investigating'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Signalement $s) => [
                    'id' => $s->id,
                    'type' => $s->type,
                    'status' => $s->status,
                    'excerpt' => mb_strimwidth((string) $s->message, 0, 140, '…'),
                    'demarche' => $s->demarche?->only(['id', 'title', 'slug']),
                    'created_at' => $s->created_at?->toIso8601String(),
                    // L'éditeur consulte ; l'adresse email du signalant n'est utile qu'au modérateur qui répond.
                    'has_email' => $admin->isModerator() ? (bool) $s->author_email : null,
                ]),
        ];
    }

    private function editorBlock(Admin $admin): array
    {
        $mine = Demarche::where('created_by', $admin->id);

        return [
            'my_drafts' => (clone $mine)->where('status', 'draft')->count(),
            'my_published' => (clone $mine)->where('status', 'published')->count(),
            'drafts' => (clone $mine)->where('status', 'draft')->latest('updated_at')->limit(5)
                ->get(['id', 'title', 'updated_at'])
                ->map(fn ($d) => ['id' => $d->id, 'title' => $d->title, 'updated_at' => $d->updated_at?->toIso8601String()]),
            'recent_updates' => Demarche::where('updated_by', $admin->id)->latest('updated_at')->limit(5)
                ->get(['id', 'title', 'status', 'updated_at'])
                ->map(fn ($d) => ['id' => $d->id, 'title' => $d->title, 'status' => $d->status, 'updated_at' => $d->updated_at?->toIso8601String()]),
        ];
    }

    /** Contenus publics en attente de modération : avis, questions et réponses. */
    private function communityBlock(): array
    {
        return [
            'pending_reviews' => DemarcheReview::where('status', 'pending')->count(),
            'pending_questions' => Question::where('status', 'pending')->count(),
            'pending_answers' => Answer::where('status', 'pending')->count(),
        ];
    }

    private function validationQueue(): array
    {
        return Demarche::where('status', 'draft')
            ->with('creator:id,name')
            ->latest('updated_at')
            ->limit(8)
            ->get(['id', 'title', 'created_by', 'updated_at'])
            ->map(fn (Demarche $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'author' => $d->creator?->name,
                'updated_at' => $d->updated_at?->toIso8601String(),
            ])
            ->all();
    }

    private function audienceBlock(): array
    {
        $today = now()->startOfDay();

        return [
            'visitors_today' => DB::table('page_views')->where('viewed_at', '>=', $today)->distinct()->count('visitor_hash'),
            'views_today' => DB::table('page_views')->where('viewed_at', '>=', $today)->count(),
            'top_demarches' => Demarche::where('status', 'published')
                ->orderByDesc('views_count')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'views_count']),
        ];
    }

    private function teamBlock(): array
    {
        $admins = Admin::query()->get(['id', 'role', 'is_active', 'locked_until', 'failed_login_attempts']);

        return [
            'total' => $admins->count(),
            'active' => $admins->where('is_active', true)->count(),
            'by_role' => collect(Admin::ROLES)->mapWithKeys(fn ($r) => [$r => $admins->where('role', $r)->count()]),
            'locked' => $admins->filter(fn ($a) => $a->locked_until && $a->locked_until->isFuture())->count(),
            'with_failed_logins' => $admins->where('failed_login_attempts', '>', 0)->count(),
        ];
    }

    private function recentActivity(): array
    {
        return ActivityLog::with('admin:id,name,role')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'admin' => $log->admin?->name,
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
