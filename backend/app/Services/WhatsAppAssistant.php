<?php

namespace App\Services;

use App\Models\Demarche;
use App\Models\SearchMiss;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Rédige la réponse de l'assistant WhatsApp à partir des fiches publiées.
 * Aucune donnée du citoyen n'est conservée (seuls les termes sans résultat sont comptés, anonymement).
 */
class WhatsAppAssistant
{
    private const MAX_LENGTH = 4000; // limite WhatsApp : 4096 caractères

    private const TEXT = [
        'fr' => [
            'welcome' => "Bonjour 👋 Je suis l'assistant DemarchesTchad.\nÉcrivez le nom d'une démarche, par exemple : *passeport*, *acte de naissance*, *carte d'identité*, *casier judiciaire*.\nJe vous donne les pièces à fournir, le coût, le délai et le lieu.",
            'cost' => 'Coût', 'delay' => 'Délai', 'where' => 'Où', 'documents' => 'Pièces à fournir', 'more' => 'Fiche complète',
            'several' => 'J’ai trouvé plusieurs démarches. Répondez avec le nom exact de celle qui vous intéresse :',
            'none' => "Je n'ai trouvé aucune démarche pour « :term ». Essayez un autre mot (ex. *passeport*) ou consultez le catalogue :",
            'disclaimer' => 'Informations indicatives : vérifiez auprès du service concerné.',
        ],
        'en' => [
            'welcome' => "Hello 👋 I am the DemarchesTchad assistant.\nType the name of a procedure, for example: *passport*, *birth certificate*, *identity card*, *criminal record*.\nI will give you the documents required, the cost, the processing time and where to go.",
            'cost' => 'Cost', 'delay' => 'Processing time', 'where' => 'Where', 'documents' => 'Documents required', 'more' => 'Full page',
            'several' => 'I found several procedures. Reply with the exact name of the one you need:',
            'none' => 'I could not find any procedure for “:term”. Try another word (e.g. *passport*) or browse the catalogue:',
            'disclaimer' => 'Information for guidance only: check with the office concerned.',
        ],
        'ar' => [
            'welcome' => "مرحبًا 👋 أنا مساعد DemarchesTchad.\nاكتب اسم الإجراء، مثل: *جواز السفر*، *شهادة الميلاد*، *بطاقة التعريف*، *السجل العدلي*.\nسأعطيك الوثائق المطلوبة والتكلفة والأجل والمكان.",
            'cost' => 'التكلفة', 'delay' => 'الأجل', 'where' => 'المكان', 'documents' => 'الوثائق المطلوبة', 'more' => 'الصفحة الكاملة',
            'several' => 'وجدت عدة إجراءات. أرسل الاسم الدقيق للإجراء الذي تحتاجه:',
            'none' => 'لم أجد أي إجراء لـ «:term». جرّب كلمة أخرى (مثل *جواز السفر*) أو تصفّح الدليل:',
            'disclaimer' => 'معلومات إرشادية: تحقّق لدى المصلحة المعنية.',
        ],
    ];

    private const GREETINGS = [
        'bonjour', 'bonsoir', 'salut', 'salam', 'menu', 'aide', 'help', 'hello', 'hi', 'hey', 'start',
        'مرحبا', 'السلام عليكم', 'سلام', 'اهلا', 'أهلا',
    ];

    public function reply(string $message): string
    {
        $message = trim($message);
        $language = $this->detectLanguage($message);
        $text = self::TEXT[$language];
        $siteUrl = rtrim((string) config('frontend.url'), '/');

        if ($message === '' || $this->isGreeting($message)) {
            return $text['welcome'] . "\n\n" . $siteUrl;
        }

        $results = $this->search($message);

        if ($results->isEmpty()) {
            SearchMiss::record($message);
            return str_replace(':term', Str::limit($message, 60), $text['none']) . "\n" . $siteUrl . '/demarches';
        }

        if ($results->count() === 1) {
            return $this->limit($this->describe($results->first(), $language, $siteUrl));
        }

        $lines = [$text['several'], ''];
        foreach ($results as $demarche) {
            $content = $demarche->getTranslatedContent($language);
            $lines[] = "• *{$content['title']}* — {$content['cost']}";
            $lines[] = "  {$siteUrl}/demarches/{$demarche->slug}";
        }

        return $this->limit(implode("\n", $lines));
    }

    public function detectLanguage(string $message): string
    {
        if (preg_match('/\p{Arabic}/u', $message)) {
            return 'ar';
        }
        if (preg_match('/\b(hello|hi|hey|help|passport|birth|death|certificate|licen[cs]e|identity|card|criminal|record|how|what|where)\b/i', $message)) {
            return 'en';
        }

        return 'fr';
    }

    private function isGreeting(string $message): bool
    {
        $normalized = Str::of($message)->lower()->replaceMatches('/[!?.,؟]+/u', '')->squish()->toString();

        return in_array($normalized, self::GREETINGS, true);
    }

    /** Recherche par la phrase entière, puis mot par mot (au moins 4 lettres), classée par pertinence. */
    private function search(string $message): Collection
    {
        $phrase = Str::of($message)->replaceMatches('/[?!.,;:؟]+/u', ' ')->squish()->toString();
        $exact = Demarche::published()->search($phrase)->orderByDesc('views_count')->limit(3)->get();
        if ($exact->isNotEmpty()) {
            return $exact;
        }

        $words = collect(preg_split('/\s+/u', mb_strtolower($phrase)))
            ->filter(fn ($word) => mb_strlen($word) >= 4)
            ->unique();
        if ($words->isEmpty()) {
            return collect();
        }

        $scores = [];
        foreach ($words as $word) {
            foreach (Demarche::published()->search($word)->pluck('id') as $id) {
                $scores[$id] = ($scores[$id] ?? 0) + 1;
            }
        }
        arsort($scores);
        $best = array_slice(array_keys($scores), 0, 3);

        return Demarche::whereIn('id', $best)->get()->sortBy(fn ($d) => array_search($d->id, $best))->values();
    }

    private function describe(Demarche $demarche, string $language, string $siteUrl): string
    {
        $text = self::TEXT[$language];
        $content = $demarche->getTranslatedContent($language);
        $lines = ["*{$content['title']}*", ''];

        $lines[] = "💰 {$text['cost']} : {$content['cost']}";
        if (filled($content['processing_time'])) {
            $lines[] = "⏱️ {$text['delay']} : {$content['processing_time']}";
        }
        if (filled($content['institution'])) {
            $lines[] = "🏛️ {$content['institution']}";
        }
        // Le lieu écrit ne vaut que pour sa ville : on la nomme (l'assistant ne connaît pas la ville de l'usager).
        if (filled($content['location'])) {
            $city = $demarche->locationCity?->localized('name', $language);
            $lines[] = $city ? "📍 {$text['where']} ({$city}) : {$content['location']}" : "📍 {$text['where']} : {$content['location']}";
        }

        $documents = $content['required_documents'] ?? [];
        if ($documents) {
            $lines[] = '';
            $lines[] = "📄 {$text['documents']} :";
            foreach (array_slice($documents, 0, 10) as $document) {
                $lines[] = "• {$document}";
            }
        }

        $lines[] = '';
        $lines[] = "🔗 {$text['more']} : {$siteUrl}/demarches/{$demarche->slug}";
        $lines[] = "_{$text['disclaimer']}_";

        return implode("\n", $lines);
    }

    private function limit(string $reply): string
    {
        return mb_strlen($reply) > self::MAX_LENGTH ? mb_substr($reply, 0, self::MAX_LENGTH - 1) . '…' : $reply;
    }
}
