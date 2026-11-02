<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Models\Admin;
use App\Models\Categorie;
use App\Models\Demarche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Alertes de l'équipe : un signalement ou un contenu du public prévient les seuls modérateurs qui l'ont demandé. */
class TeamAlertsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email, string $role, array $preferences = []): Admin
    {
        return Admin::create($preferences + [
            'name' => $email,
            'email' => $email,
            'password' => Hash::make('Secret123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function publishedDemarche(): Demarche
    {
        $categorie = Categorie::create(['name' => 'Identité', 'slug' => 'identite', 'is_active' => true]);

        return Demarche::create([
            'categorie_id' => $categorie->id,
            'title' => 'Carte nationale d’identité',
            'slug' => 'carte-nationale-identite',
            'summary' => 'Résumé',
            'description' => 'Description',
            'cost' => '5 000 FCFA',
            'cost_amount' => 5000,
            'institution' => 'Mairie',
            'location' => 'Mairie',
            'processing_time' => '2 semaines',
            'validity' => '10 ans',
            'required_documents' => ['Photo'],
            'conditions' => ['Être majeur'],
            'procedure_steps' => ['Déposer le dossier'],
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function test_signalement_alerts_only_moderators_who_enabled_it(): void
    {
        Mail::fake();
        $this->publishedDemarche();
        $superAdmin = $this->admin('super@test.td', 'super_admin');
        $moderator = $this->admin('admin@test.td', 'admin');
        $this->admin('editor@test.td', 'editor');
        $this->admin('muted@test.td', 'admin', ['notify_signalements' => false]);
        $this->admin('never@test.td', 'admin', ['notification_frequency' => 'never']);

        $this->postJson('/api/v1/signalements', [
            'demarche_id' => Demarche::first()->id,
            'type' => 'cout_different',
            'message' => 'Le prix a été révisé à la hausse au guichet.',
        ])->assertStatus(201);

        Mail::assertQueued(NotificationMail::class, 2);
        Mail::assertQueued(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($superAdmin->email));
        Mail::assertQueued(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($moderator->email));
        Mail::assertNotQueued(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo('editor@test.td'));
        Mail::assertNotQueued(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo('muted@test.td'));
        Mail::assertNotQueued(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo('never@test.td'));
    }

    public function test_question_alerts_moderators_with_community_preference(): void
    {
        Mail::fake();
        $moderator = $this->admin('admin@test.td', 'admin');
        $this->admin('quiet@test.td', 'admin', ['notify_community' => false]);

        $this->postJson('/api/v1/questions', [
            'body' => 'Faut-il une photo récente pour la carte d’identité ?',
        ])->assertStatus(201);

        Mail::assertQueued(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($moderator->email));
        Mail::assertNotQueued(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo('quiet@test.td'));
    }

    public function test_dashboard_counts_pending_public_content_for_moderators_only(): void
    {
        Mail::fake();
        $demarche = $this->publishedDemarche();
        $moderator = $this->admin('admin@test.td', 'admin');
        $editor = $this->admin('editor@test.td', 'editor');

        $this->postJson('/api/v1/questions', ['body' => 'Quels documents faut-il pour un passeport ?'])->assertStatus(201);
        $this->postJson("/api/v1/demarches/{$demarche->slug}/reviews", [
            'visit_date' => now()->subDay()->toDateString(),
            'rating' => 4,
            'comment' => 'Dossier traité en une journée, personnel courtois.',
        ])->assertStatus(201);

        $this->actingAs($moderator, 'sanctum')->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertJsonPath('data.community.pending_questions', 1)
            ->assertJsonPath('data.community.pending_reviews', 1)
            ->assertJsonPath('data.community.pending_answers', 0);

        $this->actingAs($editor, 'sanctum')->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertJsonMissingPath('data.community');
    }

    public function test_review_alert_names_the_procedure(): void
    {
        Mail::fake();
        $demarche = $this->publishedDemarche();
        $moderator = $this->admin('admin@test.td', 'admin');

        $this->postJson("/api/v1/demarches/{$demarche->slug}/reviews", [
            'visit_date' => now()->subDay()->toDateString(),
            'rating' => 2,
            'comment' => 'Le guichet était fermé à l’heure annoncée.',
        ])->assertStatus(201);

        Mail::assertQueued(NotificationMail::class, fn (NotificationMail $mail) => $mail->hasTo($moderator->email)
            && str_contains($mail->title, 'Carte nationale d’identité'));
    }
}
