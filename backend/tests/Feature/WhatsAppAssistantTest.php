<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\Demarche;
use App\Models\SearchMiss;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppAssistantTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'secret-de-test';

    private function configure(): void
    {
        config([
            'whatsapp.token' => 'jeton-de-test',
            'whatsapp.phone_number_id' => '123456',
            'whatsapp.verify_token' => 'verification-test',
            'whatsapp.app_secret' => self::SECRET,
        ]);
        $categorie = Categorie::create(['name' => 'Voyage', 'slug' => 'voyage', 'is_active' => true]);
        Demarche::create([
            'categorie_id' => $categorie->id, 'title' => 'Passeport Biométrique', 'title_ar' => 'جواز السفر البيومتري',
            'slug' => 'passeport-biometrique', 'description' => 'Document de voyage.', 'cost' => '75 000 FCFA',
            'cost_ar' => '75,000 فرنك أفريقي', 'institution' => 'DGPN', 'location' => 'Ministère de l’Intérieur',
            'status' => 'published', 'required_documents' => ['Acte de naissance', 'Carte d’identité'],
        ]);
    }

    private function message(string $text): array
    {
        return ['entry' => [['changes' => [['value' => ['messages' => [[
            'from' => '23566000000', 'type' => 'text', 'text' => ['body' => $text],
        ]]]]]]]];
    }

    private function postSigned(array $payload, ?string $secret = self::SECRET)
    {
        $body = json_encode($payload);

        return $this->call('POST', '/api/v1/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . hash_hmac('sha256', $body, (string) $secret),
        ], $body);
    }

    public function test_assistant_is_disabled_without_configuration(): void
    {
        $this->getJson('/api/v1/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=x&hub.challenge=1')->assertNotFound();
        $this->postJson('/api/v1/whatsapp/webhook', [])->assertNotFound();
    }

    public function test_webhook_verification_requires_the_right_token(): void
    {
        $this->configure();
        $this->get('/api/v1/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=verification-test&hub.challenge=42')
            ->assertOk()->assertSee('42');
        $this->get('/api/v1/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=faux&hub.challenge=42')->assertForbidden();
    }

    public function test_unsigned_or_forged_messages_are_rejected(): void
    {
        $this->configure();
        Http::fake();
        $this->postSigned($this->message('passeport'), 'mauvais-secret')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_assistant_answers_with_documents_cost_and_link_in_the_user_language(): void
    {
        $this->configure();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]])]);

        $this->postSigned($this->message('Comment faire mon passeport ?'))->assertOk();
        $this->postSigned($this->message('جواز السفر'))->assertOk();
        $this->postSigned($this->message('Bonjour'))->assertOk();
        $this->postSigned($this->message('permis de chasse'))->assertOk();

        $bodies = collect(Http::recorded())->map(fn ($pair) => $pair[0]->data()['text']['body']);
        $this->assertCount(4, $bodies);
        $this->assertStringContainsString('*Passeport Biométrique*', $bodies[0]);
        $this->assertStringContainsString('75 000 FCFA', $bodies[0]);
        $this->assertStringContainsString('• Acte de naissance', $bodies[0]);
        $this->assertStringContainsString('/demarches/passeport-biometrique', $bodies[0]);
        $this->assertStringContainsString('جواز السفر البيومتري', $bodies[1]);
        $this->assertStringContainsString('75,000 فرنك أفريقي', $bodies[1]);
        $this->assertStringContainsString("Je suis l'assistant DemarchesTchad", $bodies[2]);
        $this->assertStringContainsString('Je n\'ai trouvé aucune démarche', $bodies[3]);
        $this->assertSame('permis de chasse', SearchMiss::first()->term);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer jeton-de-test')
            && str_contains($request->url(), '/123456/messages')
            && $request['to'] === '23566000000');
    }
}
