<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Categorie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CategoryDeleteReuseTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Admin Catégories',
            'email' => 'categories@test.td',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_deleted_category_frees_its_name_and_slug(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $category = Categorie::create(['name' => 'Doublon', 'slug' => 'doublon', 'is_active' => true]);

        $this->deleteJson("/api/v1/admin/categories/{$category->id}")->assertOk();

        $response = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Doublon',
            'slug' => 'doublon',
        ]);

        $response->assertStatus(201);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertNotSame('doublon', Categorie::withTrashed()->find($category->id)->slug);
    }

    public function test_deleted_category_still_exists_in_trash_for_audit(): void
    {
        $this->actingAs($this->admin(), 'sanctum');
        $category = Categorie::create(['name' => 'Corbeille', 'slug' => 'corbeille', 'is_active' => true]);

        $this->deleteJson("/api/v1/admin/categories/{$category->id}")->assertOk();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertStringContainsString('supprimée', Categorie::withTrashed()->find($category->id)->name);
    }
}
