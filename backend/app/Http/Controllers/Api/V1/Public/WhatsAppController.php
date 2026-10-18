<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Point d'entrée de l'API WhatsApp Cloud (Meta).
 * GET  : vérification du webhook lors de sa configuration chez Meta.
 * POST : messages reçus ; chaque requête est authentifiée par la signature HMAC de Meta.
 */
class WhatsAppController extends Controller
{
    public function verify(Request $request): Response
    {
        abort_unless($this->configured(), 404);

        // PHP transforme « hub.mode » en « hub_mode » dans les paramètres de requête.
        $validToken = hash_equals((string) config('whatsapp.verify_token'), (string) $request->query('hub_verify_token'));
        abort_unless($request->query('hub_mode') === 'subscribe' && $validToken, 403);

        return response((string) $request->query('hub_challenge'), 200, ['Content-Type' => 'text/plain']);
    }

    public function receive(Request $request, WhatsAppAssistant $assistant): JsonResponse
    {
        abort_unless($this->configured(), 404);

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), (string) config('whatsapp.app_secret'));
        abort_unless(hash_equals($expected, (string) $request->header('X-Hub-Signature-256')), 403);

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                foreach ((array) ($change['value']['messages'] ?? []) as $message) {
                    $from = $message['from'] ?? null;
                    if (!$from || !preg_match('/^\d{6,20}$/', (string) $from)) {
                        continue;
                    }
                    // Seuls les messages texte reçoivent une réponse détaillée ; les autres reçoivent l'accueil.
                    $body = ($message['type'] ?? '') === 'text' ? (string) ($message['text']['body'] ?? '') : '';
                    $this->send($from, $assistant->reply(mb_substr($body, 0, 300)));
                }
            }
        }

        // Toujours 200 : sinon Meta renvoie le même message en boucle.
        return response()->json(['status' => 'ok']);
    }

    private function send(string $to, string $text): void
    {
        $url = sprintf('https://graph.facebook.com/%s/%s/messages', config('whatsapp.graph_version'), config('whatsapp.phone_number_id'));

        try {
            $response = Http::withToken((string) config('whatsapp.token'))->timeout(10)->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $text, 'preview_url' => true],
            ]);
            if ($response->failed()) {
                Log::warning('WhatsApp : envoi refusé', ['status' => $response->status()]);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp : envoi impossible', ['error' => $e->getMessage()]);
        }
    }

    private function configured(): bool
    {
        return filled(config('whatsapp.token')) && filled(config('whatsapp.phone_number_id'))
            && filled(config('whatsapp.verify_token')) && filled(config('whatsapp.app_secret'));
    }
}
