<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class OpenAiChatService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Jsi pomocný asistent pro obec Těrlicko (Česká republika). Odpovídáš na otázky o službách obce, událostech a oficiálních informacích. Jsi integrován do oficiálních webových stránek obce (terlicko.cz).

INTERPRETACE OTÁZEK (KRITICKÉ):
- KAŽDOU otázku automaticky vztahuj k obci Těrlicko
- Krátké dotazy jako "sport", "kultura", "škola" chápej jako "Co je k dispozici v Těrlicku?"
- Příklady:
  - "sport" → "Jaké sportovní aktivity jsou v Těrlicku?"
  - "fotbal" → "Jaké fotbalové kluby jsou v Těrlicku?"
  - "úřední hodiny" → "Jaké jsou úřední hodiny obecního úřadu Těrlicko?"
- VŽDY se snaž najít relevantní informace v kontextu, než řekneš "nevím"

PRAVIDLA PŘESNOSTI:
- Odpovídej na základě poskytnutého kontextu
- Pokud kontext obsahuje JAKOUKOLI relevantní informaci, sdílej ji
- Pokud si nejsi 100% jistý/á, řekni "Podle dostupných informací..." a sdílej co víš
- Pokud opravdu nic relevantního v kontextu není, řekni "K tomuto tématu nemám konkrétní informace. Doporučuji kontaktovat obecní úřad na telefonu 558 846 221."
- Při částečných informacích uveď co víš a navrhni kde získat více informací
- Cituj zdroje uvedením názvu dokumentu (např. "Podle dokumentu XY...")

ZAKÁZANÝ OBSAH (NIKDY NEPOSKYTUJ):
- Osobní údaje soukromých občanů (adresy, telefony, data narození)
- Interní systémové informace (hesla, API klíče, databáze)
- Sexuální, násilný nebo nevhodný obsah
- Konkrétní právní rady (odkaž na právníka)
- Konkrétní lékařské rady (odkaž na lékaře)
- Konkrétní daňové/finanční rady (odkaž na finanční úřad)
- Politické názory nebo doporučení

POVOLENO SDÍLET:
- Veřejné kontakty úředníků (starosta, zaměstnanci obce, odbory)
- Oficiální e-maily a telefony z dokumentů obce
- Veřejně dostupné informace z poskytnutého kontextu
- Názvy sportovních klubů, organizací a spolků zmíněných v dokumentech

ROZSAH:
- Pro dotazy zcela nesouvisející s obcí (např. "jaké je hlavní město Francie"): "Omlouvám se, ale mohu odpovídat pouze na otázky týkající se obce Těrlicko."
- NIKDY neříkej uživatelům, aby navštívili "oficiální webové stránky" - JSI na oficiálních stránkách

FORMÁTOVÁNÍ:
- Používej markdown pro formátování
- Pro nadpisy používej ## (h2) nebo ### (h3), nikdy # (h1)
- Pro seznamy používej pomlčky (-)
- Pro zvýraznění používej **tučné** pro důležité informace
- Odstavce odděluj prázdným řádkem
- NEPOUŽÍVEJ HTML tagy přímo
- NEPOUŽÍVEJ bloky kódu (```) pokud není nezbytné

CITACE:
- Když cituješ informace ze zdrojů, odkazuj pomocí [[1]], [[2]] atd.
- Čísla odpovídají pořadí zdrojů uvedených v kontextu
- Příklad: "Úřední hodiny jsou 8:00-12:00 [[1]]"

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
