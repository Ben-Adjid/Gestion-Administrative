<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use App\Models\Categorie;
use App\Models\Demarche;
use Illuminate\Support\Facades\Hash;

class DemarcheBulkDuplicateScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin', string $email = 'bulk@test.td'): Admin
    {
        return Admin::create([
            'name' => 'Admin Actions',
            'email' => $email,
            'password' => Hash::make('Secret123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function category(string $name = 'Catégorie'): Categorie
    {
        return Categorie::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(), 'is_active' => true]);
    }

    private function demarche(Categorie $category, string $title, string $status = 'draft'): Demarche
    {
        return Demarche::create([
            'categorie_id' => $category->id,
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . uniqid(),
            'description' => 'Description de test.',
            'cost' => 'Gratuit',
            'institution' => 'Administration test',
            'status' => $status,
        ]);
    }

    public function test_bulk_publish_then_unpublish_then_archive(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $cat = $this->category();
        $a = $this->demarche($cat, 'Fiche A');
        $b = $this->demarche($cat, 'Fiche B');

        $this->postJson('/api/v1/admin/demarches/bulk', ['ids' => [$a->id, $b->id], 'action' => 'publish'])
            ->assertOk()->assertJsonPath('done', 2);
        $this->assertDatabaseHas('demarches', ['id' => $a->id, 'status' => 'published']);
        $this->assertDatabaseHas('demarches', ['id' => $b->id, 'status' => 'published']);

        $this->postJson('/api/v1/admin/demarches/bulk', ['ids' => [$a->id], 'action' => 'unpublish'])
            ->assertOk()->assertJsonPath('done', 1);
        $this->assertDatabaseHas('demarches', ['id' => $a->id, 'status' => 'draft']);

        $this->postJson('/api/v1/admin/demarches/bulk', ['ids' => [$b->id], 'action' => 'archive'])
            ->assertOk()->assertJsonPath('done', 1);
        $this->assertSoftDeleted('demarches', ['id' => $b->id]);
    }

    public function test_bulk_move_changes_category(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $source = $this->category('Source bulk');
        $target = $this->category('Cible bulk');
        $a = $this->demarche($source, 'Fiche déplacée');

        $this->postJson('/api/v1/admin/demarches/bulk', [
            'ids' => [$a->id], 'action' => 'move', 'target_category_id' => $target->id,
        ])->assertOk()->assertJsonPath('done', 1);

        $this->assertDatabaseHas('demarches', ['id' => $a->id, 'categorie_id' => $target->id]);
    }

    public function test_editor_cannot_bulk_publish(): void
    {
        $this->actingAs($this->admin('editor', 'editor@test.td'), 'sanctum');
        $cat = $this->category();
        $a = $this->demarche($cat, 'Fiche éditeur');

        $this->postJson('/api/v1/admin/demarches/bulk', ['ids' => [$a->id], 'action' => 'publish'])
            ->assertStatus(403);
        $this->assertDatabaseHas('demarches', ['id' => $a->id, 'status' => 'draft']);
    }

    public function test_editor_cannot_bulk_archive(): void
    {
        $this->actingAs($this->admin('editor', 'editor3@test.td'), 'sanctum');
        $cat = $this->category();
        $a = $this->demarche($cat, 'Fiche à garder');

        $this->postJson('/api/v1/admin/demarches/bulk', ['ids' => [$a->id], 'action' => 'archive'])
            ->assertStatus(403);
        $this->assertDatabaseHas('demarches', ['id' => $a->id, 'deleted_at' => null]);
    }

    public function test_duplicate_creates_a_draft_copy_in_the_same_category(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $cat = $this->category();
        $original = $this->demarche($cat, 'Passeport', 'published');

        $response = $this->postJson("/api/v1/admin/demarches/{$original->id}/duplicate")->assertCreated();

        $copyId = $response->json('data.id');
        $this->assertDatabaseHas('demarches', [
            'id' => $copyId,
            'title' => 'Copie de Passeport',
            'categorie_id' => $cat->id,
            'status' => 'draft',
        ]);
        $this->assertNotSame($original->slug, Demarche::find($copyId)->slug);
    }

    public function test_scheduled_publication_is_published_by_the_command_when_due(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $cat = $this->category();
        $due = $this->demarche($cat, 'Fiche due');
        $later = $this->demarche($cat, 'Fiche plus tard');

        $this->putJson("/api/v1/admin/demarches/{$due->id}", [
            'scheduled_publish_at' => now()->addMinutes(10)->toISOString(),
        ])->assertOk();
        $this->putJson("/api/v1/admin/demarches/{$later->id}", [
            'scheduled_publish_at' => now()->addDays(3)->toISOString(),
        ])->assertOk();

        // La date de la première fiche est atteinte, la seconde ne l'est pas.
        Demarche::where('id', $due->id)->update(['scheduled_publish_at' => now()->subMinute()]);

        $this->artisan('demarches:publish-scheduled')->assertSuccessful();

        $this->assertDatabaseHas('demarches', ['id' => $due->id, 'status' => 'published', 'scheduled_publish_at' => null]);
        $this->assertDatabaseHas('demarches', ['id' => $later->id, 'status' => 'draft']);
    }

    public function test_editor_cannot_schedule_a_publication(): void
    {
        $this->actingAs($this->admin('editor', 'editor2@test.td'), 'sanctum');
        $cat = $this->category();
        $a = $this->demarche($cat, 'Fiche éditeur programmée');

        $this->putJson("/api/v1/admin/demarches/{$a->id}", [
            'scheduled_publish_at' => now()->addDay()->toISOString(),
        ])->assertStatus(403);
    }
}
