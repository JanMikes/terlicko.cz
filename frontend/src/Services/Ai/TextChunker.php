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
        // Token estimation for Czech text: ~2 characters per token
        // Czech uses diacritics (2 bytes each in UTF-8) and tends to have longer words
        $estimatedTokens = $this->estimateTokens($text);

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

            // Handle sentences that are too long by themselves - split them by words
            if ($sentenceTokens > $this->chunkSize) {
                // Save current chunk first if not empty
                if ($currentChunk !== '') {
                    $chunks[] = [
                        'text' => TextSanitizer::sanitizeUtf8(trim($currentChunk)),
                        'token_count' => $currentTokenCount,
                        'index' => $chunkIndex,
                    ];
                    $chunkIndex++;
                    $currentChunk = '';
                    $currentTokenCount = 0;
                }

                // Split the long sentence into smaller pieces
                $subChunks = $this->splitLongText($sentence);
                foreach ($subChunks as $subChunk) {
                    $chunks[] = [
                        'text' => TextSanitizer::sanitizeUtf8(trim($subChunk['text'])),
                        'token_count' => $subChunk['token_count'],
                        'index' => $chunkIndex,
                    ];
                    $chunkIndex++;
                }
                continue;
            }

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
     * Split long text (exceeding chunk size) into smaller pieces by words
     *
     * @return array<array{text: string, token_count: int}>
     */
    private function splitLongText(string $text): array
    {
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];
        $chunks = [];
        $currentChunk = '';
        $currentTokenCount = 0;

        // Use a slightly smaller target to leave room for safety margin
        $targetSize = (int) ($this->chunkSize * 0.9);

        foreach ($words as $word) {
            $wordTokens = $this->estimateTokens($word);

            // If a single word exceeds target, we have to include it anyway
            // but as its own chunk
            if ($wordTokens > $targetSize) {
                if ($currentChunk !== '') {
                    $chunks[] = [
                        'text' => $currentChunk,
                        'token_count' => $currentTokenCount,
                    ];
                    $currentChunk = '';
                    $currentTokenCount = 0;
                }
                $chunks[] = [
                    'text' => $word,
                    'token_count' => $wordTokens,
                ];
                continue;
            }

            if ($currentTokenCount + $wordTokens > $targetSize && $currentChunk !== '') {
                $chunks[] = [
                    'text' => $currentChunk,
                    'token_count' => $currentTokenCount,
                ];
                $currentChunk = $word;
                $currentTokenCount = $wordTokens;
            } else {
                $currentChunk .= ($currentChunk === '' ? '' : ' ') . $word;
                $currentTokenCount += $wordTokens;
            }
        }

        if ($currentChunk !== '') {
            $chunks[] = [
                'text' => $currentChunk,
                'token_count' => $currentTokenCount,
            ];
        }

        return $chunks;
    }

    /**
     * Estimate token count for Czech text
     *
     * Uses mb_strlen for accurate character count (Czech diacritics are multi-byte).
     * Czech text averages ~2 characters per token due to longer words and diacritics.
     */
    private function estimateTokens(string $text): int
    {
        return max(1, (int) (mb_strlen($text) / 2));
    }

    /**
     * Get last N tokens worth of text for overlap
     */
    private function getOverlapText(string $text, int $overlapTokens): string
    {
        // ~2 characters per token for Czech text
        $estimatedChars = $overlapTokens * 2;
        $textLength = mb_strlen($text);

        if ($textLength <= $estimatedChars) {
            return $text;
        }

        // Get last N characters, but try to break at sentence boundary
        $overlapText = mb_substr($text, -$estimatedChars);

        // Try to find the start of a sentence
        $sentenceStart = max(
            mb_strrpos($overlapText, '. ') ?: 0,
            mb_strrpos($overlapText, '! ') ?: 0,
            mb_strrpos($overlapText, '? ') ?: 0
        );

        if ($sentenceStart > 0) {
            $overlapText = mb_substr($overlapText, $sentenceStart + 2);
        }

        return trim($overlapText);
    }
}
