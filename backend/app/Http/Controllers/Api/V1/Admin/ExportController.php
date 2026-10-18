<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Demarche;
use App\Models\Signalement;
use App\Support\Csv;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * GET /api/v1/admin/export/demarches — toutes les démarches, actives et archivées.
     */
    public function demarches(Request $request): StreamedResponse
    {
        $demarches = Demarche::withTrashed()->with('categorie')->orderBy('id')->get();

        ActivityLog::logAction($request->user()->id, 'export_demarches', "Export CSV de {$demarches->count()} démarches");

        return Csv::download(
            'demarches_' . now()->format('Y-m-d') . '.csv',
            ['ID', 'Titre', 'Slug', 'Catégorie', 'Statut', 'Archivée', 'Coût', 'Délai', 'Institution', 'Lieu', 'Email', 'Téléphone', 'Pièces requises', 'Source', 'Dernière vérification', 'Mise à jour'],
            $demarches->map(fn (Demarche $d) => [
                $d->id,
                $d->title,
                $d->slug,
                $d->categorie?->name,
                $d->status,
                $d->trashed() ? 'oui' : 'non',
                $d->cost,
                $d->processing_time,
                $d->institution,
                $d->location,
                $d->contact_email,
                $d->contact_phone,
                $d->required_documents ?? [],
                $d->official_source_url,
                $d->last_verified_at?->format('Y-m-d'),
                $d->updated_at?->format('Y-m-d H:i'),
            ])
        );
    }

    /**
     * GET /api/v1/admin/export/signalements
     */
    public function signalements(Request $request): StreamedResponse
    {
        $query = Signalement::with('demarche:id,title')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $signalements = $query->get();

        ActivityLog::logAction($request->user()->id, 'export_signalements', "Export CSV de {$signalements->count()} signalements");

        return Csv::download(
            'signalements_' . now()->format('Y-m-d') . '.csv',
            ['ID', 'Date', 'Démarche', 'Type', 'Statut', 'Auteur', 'Email', 'Téléphone', 'Message', 'Notes administratives', 'Traité le'],
            $signalements->map(fn (Signalement $s) => [
                $s->id,
                $s->created_at?->format('Y-m-d H:i'),
                $s->demarche?->title,
                $s->type,
                $s->status,
                $s->author_name,
                $s->author_email,
                $s->author_phone,
                $s->message,
                $s->admin_notes,
                $s->resolved_at?->format('Y-m-d H:i'),
            ])
        );
    }
}
