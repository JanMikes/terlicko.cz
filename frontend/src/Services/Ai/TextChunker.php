<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

readonly final class TextChunker
{
    public function __construct(
        private int $chunkSize,
        private int $chunkOverlap,
    ) {
    }

    /**
     * Split text into chunks with overlap
     *
     * @return array<array{text: string, token_count: int, index: int}>
     */
    public function chunkText(string $text): array
    {
        // Simple token estimation: ~4 characters per token
        $estimatedTokens = (int) (strlen($text) / 4);

        if ($estimatedTokens <= $this->chunkSize) {
            // Text fits in one chunk
            return [[
                'text' => $text,
                'token_count' => $estimatedTokens,
                'index' => 0,
            ]];
        }

        $chunks = [];
        $sentences = $this->splitIntoSentences($text);
        $currentChunk = '';
        $currentTokenCount = 0;
        $chunkIndex = 0;

        foreach ($sentences as $sentence) {
            $sentenceTokens = $this->estimateTokens($sentence);

            // If adding this sentence would exceed chunk size, save current chunk
            if ($currentTokenCount + $sentenceTokens > $this->chunkSize && $currentChunk !== '') {
                $chunks[] = [
                    'text' => TextSanitizer::sanitizeUtf8(trim($currentChunk)),
                    'token_count' => $currentTokenCount,
                    'index' => $chunkIndex,
                ];

                // Start new chunk with overlap
                $overlapText = $this->getOverlapText($currentChunk, $this->chunkOverlap);
                $currentChunk = $overlapText . ' ' . $sentence;
                $currentTokenCount = $this->estimateTokens($currentChunk);
                $chunkIndex++;
            } else {
                $currentChunk .= ($currentChunk === '' ? '' : ' ') . $sentence;
                $currentTokenCount += $sentenceTokens;
            }
        }

        // Add the last chunk
        if ($currentChunk !== '') {
            $chunks[] = [
                'text' => TextSanitizer::sanitizeUtf8(trim($currentChunk)),
                'token_count' => $currentTokenCount,
                'index' => $chunkIndex,
            ];
        }

        return $chunks;
    }

    /**
     * Split text into sentences
     *
     * @return array<string>
     */
    private function splitIntoSentences(string $text): array
    {
        // Split on sentence boundaries (.!?) followed by whitespace
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return $sentences ?: [$text];
    }

    /**
     * Estimate token count (rough approximation)
     */
    private function estimateTokens(string $text): int
    {
        // Rough estimation: ~4 characters per token for English text
        // This is a simplification; for production you might want to use tiktoken
        return max(1, (int) (strlen($text) / 4));
    }

    /**
     * Get last N tokens worth of text for overlap
     */
    private function getOverlapText(string $text, int $overlapTokens): string
    {
        $estimatedChars = $overlapTokens * 4;
        $textLength = strlen($text);

        if ($textLength <= $estimatedChars) {
            return $text;
        }

        // Get last N characters, but try to break at sentence boundary
        $overlapText = substr($text, -$estimatedChars);

        // Try to find the start of a sentence
        $sentenceStart = max(
            strrpos($overlapText, '. '),
            strrpos($overlapText, '! '),
            strrpos($overlapText, '? ')
        );

        if ($sentenceStart !== false) {
            $overlapText = substr($overlapText, $sentenceStart + 2);
        }

        return trim($overlapText);
    }
}
