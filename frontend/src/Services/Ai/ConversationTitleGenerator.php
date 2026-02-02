<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Terlicko\Web\Entity\AiConversation;

readonly final class ConversationTitleGenerator
{
    private const PROMPT = 'Vygeneruj velmi krátký název (1-3 slova česky) pro konverzaci na základě první otázky uživatele. Odpověz POUZE názvem, bez uvozovek a bez dalšího textu.';

    public function __construct(
        private HttpClientInterface $openaiClient,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Generate a title for the conversation based on the first user message
     */
    public function generateTitle(string $userMessage): string
    {
        try {
            $response = $this->openaiClient->request('POST', 'chat/completions', [
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => self::PROMPT,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userMessage,
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 20,
                ],
            ]);

            /** @var array{choices: array<array{message: array{content: string}}>} $data */
            $data = $response->toArray();

            if (!isset($data['choices'][0]['message']['content'])) {
                return $this->fallbackTitle($userMessage);
            }

            $title = trim($data['choices'][0]['message']['content']);
            $title = trim($title, '"\'');

            // Limit to 100 characters
            if (mb_strlen($title) > 100) {
                $title = mb_substr($title, 0, 97) . '...';
            }

            return $title;
        } catch (\Throwable $e) {
            error_log('Title generation error: ' . $e->getMessage());

            return $this->fallbackTitle($userMessage);
        }
    }

    /**
     * Generate and save title for a conversation
     */
    public function generateAndSaveTitle(AiConversation $conversation, string $userMessage): string
    {
        $title = $this->generateTitle($userMessage);
        $conversation->setTitle($title);
        $this->entityManager->flush();

        return $title;
    }

    /**
     * Fallback title generation if API fails
     */
    private function fallbackTitle(string $userMessage): string
    {
        // Extract first few words from the message
        $words = preg_split('/\s+/', trim($userMessage), 4);
        if ($words === false || count($words) === 0) {
            return 'Konverzace';
        }

        $title = implode(' ', array_slice($words, 0, 3));

        if (mb_strlen($title) > 50) {
            $title = mb_substr($title, 0, 47) . '...';
        }

        return $title;
    }
}
