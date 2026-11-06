<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Categorie;
use App\Http\Resources\Api\V1\CategorieResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminCategorieControllerFull extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * GET /api/v1/admin/categories
     * Liste toutes les catégories (avec tri par ordre)
     */
    public function index(Request $request)
    {
        $query = Categorie::withCount('demarches');

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Recherche par nom
        if ($request->filled('q')) {
            $term = $request->query('q');
            $query->where(function ($search) use ($term) {
                $search->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Tri par ordre (défaut)
        $query->orderBy('display_order', 'asc');

        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);
        $categories = $query->paginate($perPage);

        return response()->json([
            'data' => CategorieResource::collection($categories),
            'meta' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
            ]
        ]);
    }

    /**
     * POST /api/v1/admin/categories
     * Créer une nouvelle catégorie
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'name_en' => 'nullable|string|max:100',
            'name_ar' => 'nullable|string|max:100',
            'slug' => 'nullable|string|max:120|alpha_dash|unique:categories,slug',
            'description' => 'nullable|string|max:1000',
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $maxOrder = Categorie::max('display_order') ?? 0;

            $categorie = Categorie::create([
                ...$validated,
                'slug' => $validated['slug'] ?? Str::slug($validated['name']),
                'color' => $validated['color'] ?? '#2563EB',
                'icon' => $validated['icon'] ?? 'folder',
                'display_order' => $validated['display_order'] ?? $maxOrder + 1,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            ActivityLog::logAction($request->user()->id, 'create_category', "Création de la catégorie « {$categorie->name} »", Categorie::class, $categorie->id);

            return response()->json([
                'message' => 'Catégorie créée avec succès',
                'data' => new CategorieResource($categorie),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create admin category.', ['exception' => $e]);
            return response()->json([
                'error' => 'Erreur lors de la création',
                'message' => 'La catégorie n’a pas pu être créée.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/admin/categories/{id}
     * Afficher une catégorie
     */
    public function show(Categorie $categorie)
    {
        return response()->json([
            'data' => new CategorieResource($categorie->loadCount('demarches')),
        ]);
    }

    /**
     * PUT /api/v1/admin/categories/{id}
     * Mettre à jour une catégorie
     */
    public function update(Request $request, Categorie $categorie)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:categories,name,' . $categorie->id,
            'name_en' => 'sometimes|nullable|string|max:100',
            'name_ar' => 'sometimes|nullable|string|max:100',
            'slug' => 'sometimes|nullable|string|max:120|alpha_dash|unique:categories,slug,' . $categorie->id,
            'description' => 'sometimes|nullable|string|max:1000',
            'description_en' => 'sometimes|nullable|string|max:1000',
            'description_ar' => 'sometimes|nullable|string|max:1000',
            'icon' => 'sometimes|nullable|string|max:50',
            'color' => ['sometimes', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'display_order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            if (isset($validated['name']) && !isset($validated['slug']) && $validated['name'] !== $categorie->name) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $categorie->update($validated);
            ActivityLog::logAction($request->user()->id, 'update_category', "Modification de la catégorie « {$categorie->name} »", Categorie::class, $categorie->id);
            return response()->json([
                'message' => 'Catégorie mise à jour avec succès',
                'data' => new CategorieResource($categorie->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update admin category.', ['category_id' => $categorie->id, 'exception' => $e]);
            return response()->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => 'La catégorie n’a pas pu être mise à jour.',
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/admin/categories/{id}
     * Supprimer une catégorie
     */
    public function destroy(Request $request, Categorie $categorie)
    {
        try {
            // Vérifier si la catégorie a des démarches
            if ($categorie->demarches()->count() > 0) {
                return response()->json([
                    'message' => __('messages.category_not_empty'),
                    'demarches_count' => $categorie->demarches()->count(),
                ], 409);
            }

            $categorie->delete();
            ActivityLog::logAction($request->user()->id, 'delete_category', "Suppression de la catégorie « {$categorie->name} »", Categorie::class, $categorie->id);

            return response()->json([
                'message' => 'Catégorie supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete admin category.', ['category_id' => $categorie->id, 'exception' => $e]);
            return response()->json([
                'error' => 'Erreur lors de la suppression',
                'message' => 'La catégorie n’a pas pu être supprimée.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/admin/categories/reorder
     * Réorganiser l'ordre des catégories
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.display_order' => 'required|integer|min:0',
        ]);

        try {
            foreach ($validated['categories'] as $item) {
                Categorie::findOrFail($item['id'])->update(['display_order' => $item['display_order']]);
            }
            \App\Models\ActivityLog::logAction($request->user()->id, 'reorder_categories', 'Réorganisation de l’ordre des catégories', Categorie::class, null,
                null, collect($validated['categories'])->pluck('display_order', 'id')->all());

            return response()->json([
                'message' => 'Catégories réorganisées avec succès',
                'data' => CategorieResource::collection(
                    Categorie::orderBy('display_order')->get()
                ),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reorder admin categories.', ['exception' => $e]);
            return response()->json([
                'error' => 'Erreur lors de la réorganisation',
                'message' => 'Les catégories n’ont pas pu être réorganisées.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/admin/categories/{id}/move-demarches
     * Déplacer toutes les démarches d'une catégorie vers une autre (archivées incluses,
     * pour que la catégorie d'origine puisse ensuite être supprimée).
     */
    public function moveDemarches(Request $request, Categorie $categorie)
    {
        $validated = $request->validate([
            'target_category_id' => ['required', 'integer', 'exists:categories,id', Rule::notIn([$categorie->id])],
        ]);

        $target = Categorie::findOrFail($validated['target_category_id']);

        try {
            $moved = \DB::transaction(function () use ($categorie, $target) {
                $count = \App\Models\Demarche::withTrashed()->where('categorie_id', $categorie->id)->count();
                if ($count > 0) {
                    \App\Models\Demarche::withTrashed()
                        ->where('categorie_id', $categorie->id)
                        ->update(['categorie_id' => $target->id, 'updated_at' => now()]);
                }
                return $count;
            });

            ActivityLog::logAction(
                $request->user()->id,
                'move_demarches_category',
                "Déplacement de {$moved} démarche(s) de « {$categorie->name} » vers « {$target->name} »",
                Categorie::class,
                $target->id,
                ['categorie_id' => $categorie->id],
                ['categorie_id' => $target->id],
            );

            return response()->json([
                'message' => $moved > 0
                    ? "{$moved} démarche(s) déplacée(s) vers « {$target->name} »."
                    : 'Cette catégorie ne contient aucune démarche à déplacer.',
                'moved' => $moved,
                'source_category_id' => $categorie->id,
                'target_category_id' => $target->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to move demarches between categories.', ['category_id' => $categorie->id, 'exception' => $e]);
            return response()->json([
                'error' => 'Erreur lors du déplacement',
                'message' => 'Les démarches n’ont pas pu être déplacées.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/admin/categories/stats/summary
     * Statistiques des catégories
     */
    public function statsSummary()
    {
        return response()->json([
            'total_categories' => Categorie::count(),
            'active_categories' => Categorie::where('is_active', true)->count(),
            'inactive_categories' => Categorie::where('is_active', false)->count(),
            'categories_with_most_demarches' => CategorieResource::collection(
                Categorie::withCount('demarches')
                    ->orderBy('demarches_count', 'desc')
                    ->limit(5)
                    ->get()
            ),
        ]);
    }
}
