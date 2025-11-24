<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

readonly final class ContextBuilder
{
    /**
     * Build context from search results
     *
     * @param array<array{chunk_id: string, document_id: string, content: string, source_url: string, title: string}> $searchResults
     * @param int $maxTokens Maximum tokens for context
     * @return array{context: string, sources: array<array{url: string, title: string}>}
     */
    public function buildContext(array $searchResults, int $maxTokens = 2000): array
    {
        $context = '';
        $sources = [];
        $seenUrls = [];
        $currentTokens = 0;

        foreach ($searchResults as $result) {
            $chunkText = $result['content'];
            $chunkTokens = $this->estimateTokens($chunkText);

            // Stop if adding this chunk would exceed max tokens
            if ($currentTokens + $chunkTokens > $maxTokens) {
                break;
            }

            // Add chunk to context
            $context .= $chunkText . "\n\n";
            $currentTokens += $chunkTokens;

            // Track source
            $sourceUrl = $result['source_url'];
            if (!in_array($sourceUrl, $seenUrls, true)) {
                $sources[] = [
                    'url' => $sourceUrl,
                    'title' => $result['title'],
                ];
                $seenUrls[] = $sourceUrl;
            }
        }

        return [
            'context' => trim($context),
            'sources' => $sources,
        ];
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
     * Estimate token count (rough approximation)
     */
    private function estimateTokens(string $text): int
    {
        return max(1, (int) (strlen($text) / 4));
    }
}
