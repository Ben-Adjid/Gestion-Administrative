<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchTerm;
use App\Models\Signalement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    public function index(): JsonResponse
    {
        $today = now()->startOfDay();
        $activeSince = now()->subMinutes(5);

        $todayViews = DB::table('page_views')->where('viewed_at', '>=', $today);
        $activeViews = DB::table('page_views')->where('viewed_at', '>=', $activeSince);

        $dailyVisitors = DB::table('page_views')
            ->where('viewed_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(viewed_at) as day, COUNT(DISTINCT visitor_hash) as visitors')
            ->groupByRaw('DATE(viewed_at)')
            ->orderBy('day')
            ->get();

        $topPages = DB::table('page_views')
            ->where('viewed_at', '>=', $today)
            ->select('path')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        // Termes les plus recherchés dans le catalogue (terme seul) et nombre de fiches trouvées à la dernière recherche.
        // Départage stable : à égalité de nombre et de date, l'ordre alphabétique fixe le résultat.
        $topSearches = SearchTerm::orderByDesc('count')->orderByDesc('last_searched_at')->orderBy('term')->limit(10)
            ->get(['term', 'count', 'last_results', 'last_searched_at']);

        // Conversion sur 30 jours : part des visiteurs uniques ayant envoyé un signalement.
        $since = now()->subDays(30);
        $visitors30 = DB::table('page_views')->where('viewed_at', '>=', $since)->distinct()->count('visitor_hash');
        $signalements30 = Signalement::withTrashed()->where('created_at', '>=', $since)->count();

        return response()->json([
            'active_sessions' => (clone $activeViews)->distinct()->count('visitor_hash'),
            'sessions_today' => (clone $todayViews)->distinct()->count('visitor_hash'),
            'views_today' => $todayViews->count(),
            'top_pages' => $topPages,
            'daily_visitors' => $dailyVisitors,
            'top_searches' => $topSearches,
            'monthly_signalements' => $this->monthlySignalements(),
            'conversion' => [
                'days' => 30,
                'visitors' => $visitors30,
                'signalements' => $signalements30,
                // null quand il n'y a encore aucun visiteur mesuré (pas de division par zéro, pas de faux 0 %).
                'rate' => $visitors30 > 0 ? round($signalements30 / $visitors30 * 100, 2) : null,
            ],
            'updated_at' => now()->toISOString(),
        ]);
    }

    /** Signalements reçus et traités par mois sur les 12 derniers mois (mois sans signalement inclus, à 0). */
    private function monthlySignalements(): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $months = [];
        for ($month = $start->copy(); $month <= now(); $month->addMonth()) {
            $months[$month->format('Y-m')] = ['month' => $month->format('Y-m'), 'received' => 0, 'handled' => 0];
        }

        $rows = Signalement::withTrashed()
            ->where(fn ($q) => $q->where('created_at', '>=', $start)->orWhere('resolved_at', '>=', $start))
            ->get(['created_at', 'resolved_at']);
        foreach ($rows as $row) {
            if ($row->created_at && isset($months[$key = $row->created_at->format('Y-m')])) {
                $months[$key]['received']++;
            }
            if ($row->resolved_at && isset($months[$key = $row->resolved_at->format('Y-m')])) {
                $months[$key]['handled']++;
            }
        }

        return array_values($months);
    }
}