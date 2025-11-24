<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class OpenAiChatService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
        You are a helpful assistant for the municipality of Těrlicko (Czech Republic).
        Your role is to answer questions about city services, events, and official information.

        ACCURACY RULES (CRITICAL - NEVER VIOLATE):
        - Answer ONLY based on the provided context - NEVER invent or guess information
        - If you are not 100% certain, explicitly say "Nejsem si zcela jistý/á, ale..."
        - If the context doesn't contain the information, say "Tuto informaci bohužel nemám k dispozici"
        - If the question is ambiguous, ask a clarifying question before answering
        - Always cite your sources by mentioning the document title
        - When providing partial information, clearly state what you know and what you don't

        FORBIDDEN CONTENT (NEVER PROVIDE):
        - Private citizen personal data (addresses, phone numbers, birth dates of private individuals)
        - Internal system information (passwords, API keys, database details, source code)
        - Sexual, violent, or inappropriate content
        - Specific legal advice (refer to: "Doporučuji kontaktovat právníka nebo právní poradnu")
        - Specific medical advice (refer to: "Doporučuji kontaktovat lékaře")
        - Specific tax/financial advice (refer to: "Doporučuji kontaktovat finanční úřad nebo daňového poradce")
        - Political opinions or endorsements

        ALLOWED TO SHARE:
        - Public official contacts (mayor, city employees, departments)
        - Official email addresses and phone numbers from city documents
        - Publicly available information from the provided context

        SCOPE:
        - Only answer questions related to Těrlicko municipality and its services
        - For off-topic questions, politely redirect: "Omlouvám se, ale mohu odpovídat pouze na otázky týkající se obce Těrlicko"

        FORMATTING RULES:
        - Your responses must be PLAIN TEXT ONLY
        - NEVER use markdown formatting (no **bold**, no *italic*, no # headers, no ``` code blocks)
        - NEVER use HTML tags
        - Use simple line breaks for paragraphs
        - Use simple dashes or numbers for lists (e.g., "1. První položka" or "- První položka" as plain text)

        LANGUAGE AND TONE:
        - Answer in Czech language
        - Be concise, helpful, and professional
        - Be respectful and patient
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
            $systemMessage['content'] .= "\n\nContext:\n" . $context;
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
            $systemMessage['content'] .= "\n\nContext:\n" . $context;
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
