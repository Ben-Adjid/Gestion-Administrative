<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Models\Admin;
use App\Models\Signalement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Formulaire « Nous contacter » : enregistrement, alerte aux administrateurs, anti-robot, validation. */
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private function moderator(): Admin
    {
        return Admin::create([
            'name' => 'Modérateur', 'email' => 'moderation@contact.test', 'password' => Hash::make('Secret123!'),
            'role' => 'admin', 'is_active' => true, 'notify_signalements' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Aminata', 'email' => 'visiteur@example.test', 'subject' => 'error',
            'message' => 'La fiche du passeport indique un ancien coût, merci de vérifier.',
        ];
    }

    public function test_a_visitor_message_is_stored_and_flagged_as_contact(): void
    {
        Mail::fake();
        $this->moderator();

        $response = $this->postJson('/api/v1/contact', $this->payload())->assertCreated();

        $signalement = Signalement::findOrFail($response->json('id'));
        $this->assertSame('pending', $signalement->status);
        $this->assertSame('visiteur@example.test', $signalement->author_email);
        $this->assertStringStartsWith('[Contact : Erreur dans une fiche]', $signalement->message);
    }

    public function test_the_team_is_alerted_by_email(): void
    {
        Mail::fake();
        $this->moderator();

        $this->postJson('/api/v1/contact', $this->payload())->assertCreated();

        Mail::assertQueued(NotificationMail::class, fn ($mail) => $mail->hasTo('moderation@contact.test'));
    }

    public function test_required_fields_and_subjects_are_checked(): void
    {
        $this->postJson('/api/v1/contact', $this->payload(['email' => 'pas-un-email']))->assertUnprocessable()->assertJsonValidationErrors(['email']);
        $this->postJson('/api/v1/contact', $this->payload(['subject' => 'inconnu']))->assertUnprocessable()->assertJsonValidationErrors(['subject']);
        $this->postJson('/api/v1/contact', $this->payload(['message' => 'court']))->assertUnprocessable()->assertJsonValidationErrors(['message']);
        $this->assertSame(0, Signalement::count());
    }

    public function test_a_robot_filling_the_hidden_field_is_silently_ignored(): void
    {
        $this->postJson('/api/v1/contact', $this->payload(['website' => 'http://spam.test']))->assertCreated();
        $this->assertSame(0, Signalement::count());
    }

    public function test_the_contact_form_is_rate_limited(): void
    {
        $this->moderator();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/contact', $this->payload())->assertCreated();
        }
        $this->postJson('/api/v1/contact', $this->payload())->assertStatus(429);
    }
}
