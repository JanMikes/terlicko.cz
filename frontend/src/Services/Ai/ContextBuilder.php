<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

readonly final class ContextBuilder
{
    /**
     * Build context from search results with structured formatting
     *
     * @param array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string}> $searchResults
     * @param int $maxTokens Maximum tokens for context
     * @return array{context: string, sources: array<array{url: string, title: string}>}
     */
    public function buildContext(array $searchResults, int $maxTokens = 2000): array
    {
        $contextParts = [];
        $sources = [];
        $seenUrls = [];
        $currentTokens = 0;
        $sourceIndex = 1;

        foreach ($searchResults as $result) {
            $chunkText = $this->cleanChunkContent($result['content']);
            $title = $result['title'];

            // Format chunk with source reference for better LLM understanding
            $formattedChunk = "[Zdroj {$sourceIndex}: {$title}]\n{$chunkText}";
            $chunkTokens = $this->estimateTokens($formattedChunk);

            // Stop if adding this chunk would exceed max tokens
            if ($currentTokens + $chunkTokens > $maxTokens) {
                break;
            }

            // Add chunk to context
            $contextParts[] = $formattedChunk;
            $currentTokens += $chunkTokens;

            // Track source
            $sourceUrl = $result['source_url'];
            if (!in_array($sourceUrl, $seenUrls, true)) {
                $sources[] = [
                    'url' => $sourceUrl,
                    'title' => $title,
                ];
                $seenUrls[] = $sourceUrl;
                $sourceIndex++;
            }
        }

        // Join with clear separator
        $context = implode("\n\n---\n\n", $contextParts);

        return [
            'context' => $context,
            'sources' => $sources,
        ];
    }

    /**
     * Clean chunk content for better embedding and context quality
     * - Removes excessive markdown formatting
     * - Normalizes whitespace
     */
    private function cleanChunkContent(string $content): string
    {
        // Remove markdown heading markers (keep the text)
        $content = preg_replace('/^#{1,6}\s*/m', '', $content) ?? $content;

        // Remove bold/italic markers (keep the text)
        $content = preg_replace('/\*{1,2}([^*]+)\*{1,2}/', '$1', $content) ?? $content;

        // Normalize multiple newlines to double newlines
        $content = preg_replace('/\n{3,}/', "\n\n", $content) ?? $content;

        // Trim whitespace
        return trim($content);
    }

    /**
     * Format sources as citations
     *
     * @param array<array{url: string, title: string}> $sources
     */
    public function formatCitations(array $sources): string
    {
        if (empty($sources)) {
            return '';
        }

        $citations = [];
        foreach ($sources as $index => $source) {
            $citations[] = sprintf('[%d] %s (%s)', $index + 1, $source['title'], $source['url']);
        }

        return implode("\n", $citations);
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
}
