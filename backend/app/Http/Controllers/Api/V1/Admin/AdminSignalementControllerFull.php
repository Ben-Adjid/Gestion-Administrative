<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Signalement;
use App\Models\ActivityLog;
use App\Http\Resources\Api\V1\SignalementResource;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSignalementControllerFull extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->middleware('auth:sanctum');
        $this->emailService = $emailService;
    }

    /**
     * GET /api/v1/admin/signalements
     * Liste tous les signalements (avec filtres)
     */
    public function index(Request $request)
    {
        $query = Signalement::with('demarche');

        // Filtrer par statut
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filtrer par type
        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        // Recherche dans les champs réellement stockés par le signalement.
        if ($request->filled('q')) {
            $term = (string) $request->query('q');
            $searchContacts = $request->user()->isModerator();
            $query->where(function ($q) use ($term, $searchContacts) {
                $q->where('message', 'like', "%{$term}%");
                if ($searchContacts) {
                    $q->orWhere('author_email', 'like', "%{$term}%")
                      ->orWhere('author_name', 'like', "%{$term}%");
                }
            });
        }

        // Filtrer par date
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->query('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->query('to_date'));
        }

        // Tri
        $sort = $request->query('sort', 'created_at');
        $sort = in_array($sort, ['created_at', 'status', 'type'], true) ? $sort : 'created_at';
        $direction = $request->query('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction);

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $signalements = $query->paginate($perPage);

        return response()->json([
            'data' => SignalementResource::collection($signalements),
            'meta' => [
                'total' => $signalements->total(),
                'per_page' => $signalements->perPage(),
                'current_page' => $signalements->currentPage(),
                'last_page' => $signalements->lastPage(),
            ]
        ]);
    }

    /**
     * GET /api/v1/admin/signalements/{id}
     * Afficher un signalement
     */
    public function show(Signalement $signalement)
    {
        $signalement->load('demarche');

        return response()->json([
            'data' => new SignalementResource($signalement),
        ]);
    }

    /**
     * PATCH /api/v1/admin/signalements/{id}/status
     * Mettre à jour le statut d'un signalement
     */
    public function updateStatus(Request $request, Signalement $signalement)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,investigating,resolved,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $oldStatus = $signalement->status;
            $newStatus = $validated['status'];
            $notes = $validated['admin_notes'] ?? $signalement->admin_notes;
            $isResolved = in_array($newStatus, ['resolved', 'rejected'], true);

            $signalement->update([
                'status' => $newStatus,
                'admin_notes' => $notes,
                'resolved_by' => $isResolved ? $request->user()->id : null,
                'resolved_at' => $isResolved ? now() : null,
            ]);

            $emailQueued = false;
            if (!empty($validated['admin_notes']) && $signalement->author_email) {
                // Le statut est déjà enregistré : un échec d'envoi ne doit pas annoncer un échec de la modération.
                try {
                    $this->emailService->sendSignalementResponse($signalement, $validated['admin_notes']);
                    $emailQueued = true;
                } catch (\Throwable $mailError) {
                    Log::error('Réponse au signalant non envoyée.', ['signalement_id' => $signalement->id, 'exception' => $mailError]);
                }
            }

            ActivityLog::logAction(
                $request->user()->id,
                'moderate_signalement',
                "Mise à jour du signalement #{$signalement->id} ({$oldStatus} → {$newStatus})",
                Signalement::class,
                $signalement->id,
                ['status' => $oldStatus],
                ['status' => $newStatus]
            );

            return response()->json([
                'message' => 'Signalement mis à jour avec succès',
                'data' => new SignalementResource($signalement->fresh()->load('demarche')),
                'status_changed' => [
                    'from' => $oldStatus,
                    'to' => $newStatus,
                ],
                'email_queued' => $emailQueued,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update admin signalement.', ['signalement_id' => $signalement->id, 'exception' => $e]);
            return response()->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => 'Le signalement n’a pas pu être mis à jour.',
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/admin/signalements/{id}
     * Supprimer un signalement (soft delete)
     */
    public function destroy(Signalement $signalement)
    {
        try {
            $signalement->delete();

            return response()->json([
                'message' => 'Signalement supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete admin signalement.', ['signalement_id' => $signalement->id, 'exception' => $e]);
            return response()->json([
                'error' => 'Erreur lors de la suppression',
                'message' => 'Le signalement n’a pas pu être supprimé.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/admin/signalements/{id}/restore
     * Restaurer un signalement supprimé
     */
    public function restore($id)
    {
        try {
            $signalement = Signalement::onlyTrashed()->findOrFail($id);
            $signalement->restore();

            return response()->json([
                'message' => 'Signalement restauré avec succès',
                'data' => new SignalementResource($signalement),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to restore admin signalement.', ['signalement_id' => $id, 'exception' => $e]);
            return response()->json([
                'error' => 'Erreur lors de la restauration',
                'message' => 'Le signalement n’a pas pu être restauré.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/admin/signalements/stats/summary
     * Statistiques des signalements
     */
    public function statsSummary()
    {
        return response()->json([
            'total_signalements' => Signalement::count(),
            'pending_signalements' => Signalement::where('status', 'pending')->count(),
            'investigating_signalements' => Signalement::where('status', 'investigating')->count(),
            'resolved_signalements' => Signalement::where('status', 'resolved')->count(),
            'rejected_signalements' => Signalement::where('status', 'rejected')->count(),
            'by_type' => Signalement::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'average_resolution_time_hours' => round(
                Signalement::whereNotNull('resolved_at')
                    ->get(['created_at', 'resolved_at'])
                    ->avg(fn (Signalement $signalement) => $signalement->created_at->diffInHours($signalement->resolved_at)) ?? 0,
                2
            ),
        ]);
    }
}
