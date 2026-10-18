<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Demarche;
use App\Services\DemarcheImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Import de démarches depuis Excel/CSV (modérateurs). Deux temps :
 * 1. dry_run=1 : vérification, aucune écriture, rapport ligne par ligne ;
 * 2. import réel : uniquement si aucune erreur, toutes les lignes en brouillon.
 */
class AdminImportController extends Controller
{
    public function template(DemarcheImporter $importer): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'modele') . '.xlsx';
        $importer->writeTemplate($path);

        return response()->download($path, 'modele-import-demarches.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    public function import(Request $request, DemarcheImporter $importer): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:2048'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'csv'], true)) {
            return response()->json(['message' => __('messages.import_invalid_file'), 'errors' => ['file' => [__('messages.import_invalid_file')]]], 422);
        }

        try {
            $rows = $importer->readRows($file->getRealPath(), $extension);
        } catch (\Throwable) {
            return response()->json(['message' => __('messages.import_invalid_file'), 'errors' => ['file' => [__('messages.import_invalid_file')]]], 422);
        }

        if (count($rows) > DemarcheImporter::MAX_ROWS) {
            return response()->json(['message' => __('validation.max.array', ['attribute' => 'file', 'max' => DemarcheImporter::MAX_ROWS])], 422);
        }

        $result = $importer->analyse($rows);
        $report = [
            'rows' => count($rows),
            'valid' => count($result['valid']),
            'errors' => collect($result['errors'])->map(fn ($messages, $line) => ['line' => $line, 'messages' => $messages])->values(),
            'preview' => collect($result['valid'])->take(20)->map(fn ($data, $line) => ['line' => $line, 'title' => $data['title'], 'cost' => $data['cost']])->values(),
            'created' => 0,
        ];

        if ($request->boolean('dry_run') || $result['errors'] || !$result['valid']) {
            return response()->json(['data' => $report]);
        }

        $report['created'] = DB::transaction(fn () => $importer->create($result['valid'], $request->user()->id));
        ActivityLog::logAction($request->user()->id, 'import_demarches', "Import de {$report['created']} démarche(s) en brouillon", Demarche::class, null);

        return response()->json(['data' => $report], 201);
    }
}
