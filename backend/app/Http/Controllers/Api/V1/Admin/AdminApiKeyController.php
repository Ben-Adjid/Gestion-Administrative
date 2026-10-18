<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Clés API des partenaires (cahier des charges, module 9) — réservé au super administrateur. */
class AdminApiKeyController extends Controller
{
    /** GET /api/v1/admin/api-keys */
    public function index(): JsonResponse
    {
        return response()->json(['data' => ApiKey::orderByDesc('created_at')->get()->map(fn (ApiKey $key) => $this->present($key))]);
    }

    /** POST /api/v1/admin/api-keys — la clé en clair n'est renvoyée qu'ici, une seule fois. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:150']);

        [$key, $plain] = ApiKey::generate($validated['name'], $request->user()->id);

        ActivityLog::logAction($request->user()->id, 'api_key_created', "Création de la clé API « {$key->name} » ({$key->key_prefix}…)", ApiKey::class, $key->id);

        return response()->json(['data' => $this->present($key) + ['key' => $plain]], 201);
    }

    /** PATCH /api/v1/admin/api-keys/{apiKey} — activer / révoquer, renommer. */
    public function update(Request $request, ApiKey $apiKey): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'is_active' => 'sometimes|required|boolean',
        ]);

        $old = $apiKey->only(array_keys($validated));
        $apiKey->update($validated);

        ActivityLog::logAction($request->user()->id, 'api_key_updated', "Modification de la clé API « {$apiKey->name} » ({$apiKey->key_prefix}…)", ApiKey::class, $apiKey->id, $old, $validated);

        return response()->json(['data' => $this->present($apiKey)]);
    }

    /** DELETE /api/v1/admin/api-keys/{apiKey} */
    public function destroy(Request $request, ApiKey $apiKey): JsonResponse
    {
        ActivityLog::logAction($request->user()->id, 'api_key_deleted', "Suppression de la clé API « {$apiKey->name} » ({$apiKey->key_prefix}…)", ApiKey::class, $apiKey->id, ['requests_count' => $apiKey->requests_count]);
        $apiKey->delete();

        return response()->json(null, 204);
    }

    private function present(ApiKey $key): array
    {
        return [
            'id' => $key->id,
            'name' => $key->name,
            'key_prefix' => $key->key_prefix,
            'requests_count' => $key->requests_count,
            'last_used_at' => $key->last_used_at?->toIso8601String(),
            'is_active' => $key->is_active,
            'created_at' => $key->created_at?->toIso8601String(),
        ];
    }
}
