<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use App\Models\Categorie;
use App\Models\Demarche;
use Illuminate\Support\Facades\Hash;

class CategoryMoveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Admin Déplacement',
            'email' => 'move@test.td',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function demarche(Categorie $category, string $title): Demarche
    {
        return Demarche::create([
            'categorie_id' => $category->id,
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . uniqid(),
            'description' => 'Description de test.',
            'cost' => 'Gratuit',
            'institution' => 'Administration test',
            'status' => 'draft',
        ]);
    }

    public function test_admin_can_move_all_demarches_including_archived_to_another_category(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $source = Categorie::create(['name' => 'Source', 'slug' => 'source', 'is_active' => true]);
        $target = Categorie::create(['name' => 'Cible', 'slug' => 'cible', 'is_active' => true]);

        $a = $this->demarche($source, 'Démarche A');
        $b = $this->demarche($source, 'Démarche B');
        $archived = $this->demarche($source, 'Démarche archivée');
        $archived->delete();
        $other = $this->demarche($target, 'Démarche déjà ailleurs');

        $this->postJson("/api/v1/admin/categories/{$source->id}/move-demarches", [
            'target_category_id' => $target->id,
        ])->assertOk()
            ->assertJsonPath('moved', 3);

        $this->assertDatabaseHas('demarches', ['id' => $a->id, 'categorie_id' => $target->id]);
        $this->assertDatabaseHas('demarches', ['id' => $b->id, 'categorie_id' => $target->id]);
        $this->assertDatabaseHas('demarches', ['id' => $archived->id, 'categorie_id' => $target->id]);
        $this->assertDatabaseHas('demarches', ['id' => $other->id, 'categorie_id' => $target->id]);

        // La catégorie d'origine est vide : elle peut maintenant être supprimée.
        $this->deleteJson("/api/v1/admin/categories/{$source->id}")->assertOk();
    }

    public function test_moving_from_an_empty_category_moves_nothing(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $source = Categorie::create(['name' => 'Vide', 'slug' => 'vide', 'is_active' => true]);
        $target = Categorie::create(['name' => 'Cible vide', 'slug' => 'cible-vide', 'is_active' => true]);

        $this->postJson("/api/v1/admin/categories/{$source->id}/move-demarches", [
            'target_category_id' => $target->id,
        ])->assertOk()->assertJsonPath('moved', 0);
    }

    public function test_cannot_move_demarches_to_the_same_category(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $source = Categorie::create(['name' => 'Même', 'slug' => 'meme', 'is_active' => true]);
        $this->demarche($source, 'Démarche');

        $this->postJson("/api/v1/admin/categories/{$source->id}/move-demarches", [
            'target_category_id' => $source->id,
        ])->assertStatus(422);

        $this->assertSame(1, Demarche::where('categorie_id', $source->id)->count());
    }

    public function test_cannot_move_demarches_to_an_unknown_category(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $source = Categorie::create(['name' => 'Inconnue', 'slug' => 'inconnue', 'is_active' => true]);

        $this->postJson("/api/v1/admin/categories/{$source->id}/move-demarches", [
            'target_category_id' => 999999,
        ])->assertStatus(422);
    }
}
