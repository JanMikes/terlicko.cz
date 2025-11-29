<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class QueryNormalizerService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Jsi pomocník pro vyhledávání. Tvým úkolem je převést uživatelský dotaz na klíčová slova pro vyhledávání v databázi obce Těrlicko.

PRAVIDLA:
1. Převeď všechna slova do základního tvaru (1. pád jednotného čísla)
2. Přidej synonyma a související pojmy
3. Vždy přidej "Těrlicko" pokud tam není
4. Výstup: pouze klíčová slova oddělená mezerami, BEZ celých vět

PŘÍKLADY:
"Kdo je starostou obce?" → "starosta vedení obce Těrlicko"
"Kde najdu informace o odpadech?" → "odpad odpady svoz komunální Těrlicko"
"Jaké jsou úřední hodiny?" → "úřední hodiny otevírací doba úřad Těrlicko"
"Kolik má obec obyvatel?" → "obyvatelé počet populace Těrlicko"
"Kontakt na místostarostu" → "místostarosta kontakt telefon email vedení Těrlicko"
PROMPT;

    public function __construct(
        private HttpClientInterface $openaiClient,
        private CacheItemPoolInterface $cache,
        private string $chatModel,
    ) {
    }

    public function normalizeQuery(string $query): string
    {
        $cacheKey = 'query_norm_' . md5($query);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            /** @var string */
            return $cacheItem->get();
        }

        $response = $this->openaiClient->request('POST', 'chat/completions', [
            'json' => [
                'model' => $this->chatModel,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => $query],
                ],
                'temperature' => 0.3,
                'max_tokens' => 100,
            ],
        ]);

        /** @var array{choices: array<array{message: array{content: string}}>} $data */
        $data = $response->toArray();
        $normalized = trim($data['choices'][0]['message']['content'] ?? $query);

        // Cache for 24 hours
        $cacheItem->set($normalized);
        $cacheItem->expiresAfter(86400);
        $this->cache->save($cacheItem);

        return $normalized;
    }
}
