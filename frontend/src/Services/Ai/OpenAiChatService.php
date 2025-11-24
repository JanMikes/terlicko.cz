<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class OpenAiChatService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
        You are a helpful assistant for the municipality of Těrlicko (Czech Republic).
        Your role is to answer questions about city services, events, and official information.

        CRITICAL FORMATTING RULE:
        - Your responses must be PLAIN TEXT ONLY
        - NEVER use markdown formatting (no **bold**, no *italic*, no # headers, no - lists, no ``` code blocks)
        - NEVER use HTML tags
        - Use simple line breaks for paragraphs
        - Use simple dashes or numbers for lists (e.g., "1. První položka" or "- První položka" as plain text)
        - This is essential because responses are streamed and the frontend cannot render any formatting

        Guidelines:
        - Answer in Czech language
        - Be concise and helpful
        - Base your answers ONLY on the provided context
        - If the context doesn't contain relevant information, politely say you don't have that information
        - Always cite your sources by mentioning the document title
        - Be respectful and professional
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
