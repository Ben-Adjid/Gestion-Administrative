<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardRoleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role): Admin
    {
        return Admin::create([
            'name' => ucfirst($role),
            'email' => "{$role}@dashboard.test",
            'password' => Hash::make('Secret123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_editor_dashboard_contains_editor_work_without_moderator_or_team_data(): void
    {
        $this->actingAs($this->admin('editor'), 'sanctum');

        $this->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertJsonPath('data.role', 'editor')
            ->assertJsonPath('data.capabilities.moderate_signalements', false)
            ->assertJsonStructure(['data' => ['my_work' => ['my_drafts', 'my_published', 'drafts', 'recent_updates']]])
            ->assertJsonMissingPath('data.validation_queue')
            ->assertJsonMissingPath('data.audience')
            ->assertJsonMissingPath('data.team')
            ->assertJsonMissingPath('data.recent_activity');
    }

    public function test_admin_dashboard_contains_moderation_data_but_not_team_data(): void
    {
        $this->actingAs($this->admin('admin'), 'sanctum');

        $this->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.capabilities.manage_categories', true)
            ->assertJsonStructure(['data' => ['validation_queue', 'audience']])
            ->assertJsonMissingPath('data.team')
            ->assertJsonMissingPath('data.recent_activity')
            ->assertJsonMissingPath('data.my_work');
    }

    public function test_super_admin_dashboard_contains_team_and_audit_activity(): void
    {
        $this->actingAs($this->admin('super_admin'), 'sanctum');

        $this->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertJsonPath('data.role', 'super_admin')
            ->assertJsonPath('data.capabilities.manage_team', true)
            ->assertJsonStructure(['data' => ['team' => ['total', 'active', 'by_role'], 'recent_activity']])
            ->assertJsonMissingPath('data.my_work');
    }
}
