<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GeoUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import du référentiel géographique : aperçu puis enregistrement. Rien n'est écrit tant que
 * toutes les lignes ne sont pas valides ; un import partiel n'est jamais enregistré.
 * Colonnes : level, parent_slug, name, status, source_url, source_label, verified_on.
 */
class AdminGeoImportController extends Controller
{
    private const MAX_ROWS = 2000;

    /** Niveau de parent autorisé pour chaque niveau (même règle que le référentiel). */
    private const PARENT_LEVEL = [
        'province' => null,
        'department' => ['province'],
        'commune' => ['department'],
        'locality' => ['province', 'department', 'commune'],
    ];

    public function preview(Request $request): JsonResponse
    {
        return response()->json($this->analyse($request));
    }

    public function commit(Request $request): JsonResponse
    {
        $analysis = $this->analyse($request);
        if ($analysis['errors'] > 0) {
            return response()->json(['message' => 'Import refusé : corrigez les lignes en erreur.'] + $analysis, 422);
        }

        $created = DB::transaction(function () use ($analysis, $request) {
            $count = 0;
            foreach ($analysis['rows'] as $row) {
                $data = $row['data'];
                $parent = $data['parent_slug'] ? GeoUnit::where('slug', $data['parent_slug'])->first() : null;
                $unit = GeoUnit::create([
                    'parent_id' => $parent?->id,
                    'level' => $data['level'],
                    'name' => $data['name'],
                    'slug' => $this->uniqueSlug($data['name']),
                    'status' => $data['status'],
                    'source_label' => $data['source_label'],
                    'source_url' => $data['source_url'],
                    'verified_on' => $data['verified_on'],
                    'is_active' => true,
                ]);
                $count++;
                ActivityLog::logAction($request->user()->id, 'import_geo_unit', "Import de « {$unit->name} » ({$unit->level})", GeoUnit::class, $unit->id);
            }

            return $count;
        });

        return response()->json(['message' => "{$created} unité(s) importée(s).", 'created' => $created]);
    }

    /** Vérifie chaque ligne sans rien écrire. */
    private function analyse(Request $request): array
    {
        $rows = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:' . self::MAX_ROWS],
            'rows.*.level' => ['nullable', 'string'],
            'rows.*.parent_slug' => ['nullable', 'string', 'max:120'],
            'rows.*.name' => ['nullable', 'string', 'max:120'],
            'rows.*.status' => ['nullable', 'string'],
            'rows.*.source_url' => ['nullable', 'string', 'max:500'],
            'rows.*.source_label' => ['nullable', 'string', 'max:255'],
            'rows.*.verified_on' => ['nullable', 'string', 'max:20'],
        ])['rows'];

        $seen = [];
        $analysed = [];
        $errors = 0;

        foreach ($rows as $index => $raw) {
            $data = [
                'level' => trim((string) ($raw['level'] ?? '')),
                'parent_slug' => trim((string) ($raw['parent_slug'] ?? '')) ?: null,
                'name' => trim((string) ($raw['name'] ?? '')),
                'status' => trim((string) ($raw['status'] ?? '')) ?: 'to_verify',
                'source_url' => trim((string) ($raw['source_url'] ?? '')) ?: null,
                'source_label' => trim((string) ($raw['source_label'] ?? '')) ?: null,
                'verified_on' => trim((string) ($raw['verified_on'] ?? '')) ?: null,
            ];
            $problems = $this->problems($data, $seen);
            if ($problems === []) {
                $seen[] = $data['level'] . '|' . ($data['parent_slug'] ?? '') . '|' . mb_strtolower($data['name']);
            } else {
                $errors++;
            }
            $analysed[] = ['line' => $index + 2, 'data' => $data, 'problems' => $problems];
        }

        return [
            'rows' => $analysed,
            'total' => count($analysed),
            'errors' => $errors,
            'valid' => count($analysed) - $errors,
        ];
    }

    /** @return array<int, string> */
    private function problems(array $data, array $seen): array
    {
        $problems = [];
        if (!in_array($data['level'], GeoUnit::LEVELS, true)) {
            $problems[] = 'Niveau inconnu (province, department, commune ou locality).';
        }
        if ($data['name'] === '' || mb_strlen($data['name']) > 120) {
            $problems[] = 'Nom obligatoire (120 caractères maximum).';
        }
        if (!in_array($data['status'], GeoUnit::STATUSES, true)) {
            $problems[] = 'Statut inconnu (official ou to_verify).';
        }
        if ($data['status'] === 'official' && (blank($data['source_url']) || blank($data['verified_on']))) {
            $problems[] = 'Statut officiel : source et date de vérification obligatoires.';
        }
        if ($data['verified_on'] !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['verified_on'])) {
            $problems[] = 'Date de vérification au format AAAA-MM-JJ.';
        }
        if ($data['source_url'] !== null && !filter_var($data['source_url'], FILTER_VALIDATE_URL)) {
            $problems[] = 'Source : adresse web invalide.';
        }

        if (in_array($data['level'], GeoUnit::LEVELS, true)) {
            $allowed = self::PARENT_LEVEL[$data['level']];
            $parent = $data['parent_slug'] ? GeoUnit::where('slug', $data['parent_slug'])->first() : null;
            if ($allowed === null && $data['parent_slug'] !== null) {
                $problems[] = 'Une province n’a pas de rattachement.';
            } elseif ($allowed !== null && ($parent === null || !in_array($parent->level, $allowed, true))) {
                $problems[] = 'Rattachement absent ou de niveau incompatible (slug du parent existant).';
            }

            $duplicate = GeoUnit::where('level', $data['level'])
                ->where('name', $data['name'])
                ->when($parent, fn ($q) => $q->where('parent_id', $parent->id), fn ($q) => $q->whereNull('parent_id'))
                ->exists();
            if ($duplicate || in_array($data['level'] . '|' . ($data['parent_slug'] ?? '') . '|' . mb_strtolower($data['name']), $seen, true)) {
                $problems[] = 'Doublon : cette unité existe déjà à ce niveau et sous ce rattachement.';
            }
        }

        return $problems;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'unite';
        $slug = $base;
        for ($i = 2; GeoUnit::where('slug', $slug)->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}
