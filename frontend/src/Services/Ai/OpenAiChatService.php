<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class OpenAiChatService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Jsi pomocný asistent pro obec Těrlicko (Česká republika). Odpovídáš na otázky o službách obce, událostech a oficiálních informacích. Jsi integrován do oficiálních webových stránek obce (terlicko.cz).

PRAVIDLA PŘESNOSTI (KRITICKÉ - NIKDY NEPORUŠUJ):
- Odpovídej POUZE na základě poskytnutého kontextu - NIKDY nevymýšlej ani neháděj informace
- Pokud si nejsi 100% jistý/á, řekni "Nejsem si zcela jistý/á, ale..."
- Pokud kontext neobsahuje informaci, řekni "Tuto informaci bohužel nemám k dispozici. Zkuste prosím kontaktovat obecní úřad."
- Pokud je otázka nejednoznačná, nejprve polož upřesňující otázku
- Vždy cituj zdroje uvedením názvu dokumentu (např. "Podle dokumentu XY...")
- Při poskytování částečných informací jasně uveď, co víš a co ne
- Pokud najdeš JAKOUKOLI relevantní informaci v kontextu, sdílej ji, i když je neúplná

ZAKÁZANÝ OBSAH (NIKDY NEPOSKYTUJ):
- Osobní údaje soukromých občanů (adresy, telefony, data narození)
- Interní systémové informace (hesla, API klíče, databáze)
- Sexuální, násilný nebo nevhodný obsah
- Konkrétní právní rady (odkaž na: "Doporučuji kontaktovat právníka")
- Konkrétní lékařské rady (odkaž na: "Doporučuji kontaktovat lékaře")
- Konkrétní daňové/finanční rady (odkaž na: "Doporučuji kontaktovat finanční úřad")
- Politické názory nebo doporučení

POVOLENO SDÍLET:
- Veřejné kontakty úředníků (starosta, zaměstnanci obce, odbory)
- Oficiální e-maily a telefony z dokumentů obce
- Veřejně dostupné informace z poskytnutého kontextu
- Názvy sportovních klubů, organizací a spolků zmíněných v dokumentech

ROZSAH:
- Odpovídej pouze na otázky týkající se obce Těrlicko a jejích služeb
- Pro off-topic otázky: "Omlouvám se, ale mohu odpovídat pouze na otázky týkající se obce Těrlicko"
- NIKDY neříkej uživatelům, aby navštívili "oficiální webové stránky" - JSI na oficiálních stránkách

FORMÁTOVÁNÍ:
- Odpovědi musí být POUZE PROSTÝ TEXT
- NEPOUŽÍVEJ markdown (žádné **tučné**, *kurzíva*, # nadpisy, ``` bloky kódu)
- NEPOUŽÍVEJ HTML tagy
- Pro odstavce používej jednoduché odřádkování
- Pro seznamy používej jednoduché pomlčky nebo čísla jako prostý text

JAZYK A TÓN:
- Odpovídej česky
- Buď stručný, nápomocný a profesionální
PROMPT;

    public function __construct(
        private HttpClientInterface $openaiClient,
        private string $chatModel,
    ) {
    }

    /**
     * Generate chat completion
     *
     * @param array<array{role: string, content: string}> $messages
     * @param string|null $context Retrieved context for RAG
     * @return array{content: string, model: string, tokens: array{prompt: int, completion: int, total: int}}
     */
    public function generateCompletion(array $messages, ?string $context = null): array
    {
        $systemMessage = [
            'role' => 'system',
            'content' => self::SYSTEM_PROMPT,
        ];

        if ($context !== null && $context !== '') {
            $systemMessage['content'] .= "\n\n=== KONTEXT Z DOKUMENTŮ OBCE ===\n" . $context . "\n=== KONEC KONTEXTU ===";
        }

        $allMessages = array_merge([$systemMessage], $messages);

        $response = $this->openaiClient->request('POST', 'chat/completions', [
            'json' => [
                'model' => $this->chatModel,
                'messages' => $allMessages,
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ],
        ]);

        /** @var array{choices: array<array{message: array{content: string}}>, model: string, usage: array{prompt_tokens: int, completion_tokens: int, total_tokens: int}} $data */
        $data = $response->toArray();

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Invalid response from OpenAI chat API');
        }

        return [
            'content' => $data['choices'][0]['message']['content'],
            'model' => $data['model'],
            'tokens' => [
                'prompt' => $data['usage']['prompt_tokens'],
                'completion' => $data['usage']['completion_tokens'],
                'total' => $data['usage']['total_tokens'],
            ],
        ];
    }

    /**
     * Generate streaming chat completion
     *
     * @param array<array{role: string, content: string}> $messages
     * @param string|null $context Retrieved context for RAG
     * @return iterable<string> Stream of content chunks
     */
    public function generateStreamingCompletion(array $messages, ?string $context = null): iterable
    {
        $systemMessage = [
            'role' => 'system',
            'content' => self::SYSTEM_PROMPT,
        ];

        if ($context !== null && $context !== '') {
            $systemMessage['content'] .= "\n\n=== KONTEXT Z DOKUMENTŮ OBCE ===\n" . $context . "\n=== KONEC KONTEXTU ===";
        }

        $allMessages = array_merge([$systemMessage], $messages);

        $response = $this->openaiClient->request('POST', 'chat/completions', [
            'json' => [
                'model' => $this->chatModel,
                'messages' => $allMessages,
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'stream' => true,
            ],
        ]);

        foreach ($this->openaiClient->stream($response) as $chunk) {
            $content = $chunk->getContent();

            // Parse SSE format: "data: {...}\n\n"
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                if (str_starts_with($line, 'data: ')) {
                    $jsonData = substr($line, 6);

                    if ($jsonData === '[DONE]') {
                        break;
                    }

                    /** @var array{choices?: array<array{delta?: array{content?: string}}>}|null $parsed */
                    $parsed = json_decode($jsonData, true);
                    if (isset($parsed['choices'][0]['delta']['content'])) {
                        yield (string) $parsed['choices'][0]['delta']['content'];
                    }
                }
            }
        }
    }
}
