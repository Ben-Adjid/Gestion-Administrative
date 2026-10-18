<?php

namespace App\Services;

use App\Models\Demarche;
use App\Models\Signalement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Chiffres du rapport mensuel envoyé à l'équipe (fréquentation, signalements, contenu).
 * Aucune donnée personnelle : uniquement des totaux et des titres de fiches.
 */
class MonthlyReport
{
    /** @return array<string, mixed> */
    public function build(CarbonImmutable $month): array
    {
        $start = $month->startOfMonth();
        $end = $month->endOfMonth();
        $period = [$start, $end];

        $views = DB::table('page_views')->whereBetween('viewed_at', $period);

        $topPaths = (clone $views)
            ->where('path', 'like', '/demarches/%')
            ->select('path', DB::raw('COUNT(*) as total'))
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $titles = Demarche::whereIn('slug', $topPaths->map(fn ($row) => substr($row->path, strlen('/demarches/'))))
            ->pluck('title', 'slug');

        $signalements = Signalement::withTrashed()->whereBetween('created_at', $period);

        return [
            'month' => $start,
            'page_views' => (clone $views)->count(),
            'unique_visitors' => (clone $views)->distinct('visitor_hash')->count('visitor_hash'),
            'top_demarches' => $topPaths->map(fn ($row) => [
                'title' => $titles[substr($row->path, strlen('/demarches/'))] ?? $row->path,
                'views' => (int) $row->total,
            ])->all(),
            'signalements_received' => (clone $signalements)->count(),
            'signalements_resolved' => Signalement::whereBetween('resolved_at', $period)->count(),
            'signalements_pending' => Signalement::where('status', 'pending')->count(),
            'new_citizens' => User::whereBetween('created_at', $period)->count(),
            'demarches_updated' => Demarche::whereBetween('updated_at', $period)->count(),
            'demarches_published' => Demarche::published()->count(),
            'demarches_never_verified' => Demarche::published()->whereNull('last_verified_at')->count(),
        ];
    }
}
