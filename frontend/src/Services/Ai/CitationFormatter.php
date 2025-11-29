<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

readonly final class CitationFormatter
{
    private const INITIAL_SOURCES_COUNT = 4;
    private const MIN_WEBPAGES_IN_INITIAL = 2;

    /**
     * Format sources as JSON citations
     *
     * @param array<array{url: string, title: string, type?: string}> $sources
     */
    public function formatAsJson(array $sources): string
    {
        return json_encode($sources, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Format sources as markdown list
     *
     * @param array<array{url: string, title: string, type?: string}> $sources
     */
    public function formatAsMarkdown(array $sources): string
    {
        if (empty($sources)) {
            return '';
        }

        $markdown = "**Zdroje:**\n\n";
        foreach ($sources as $source) {
            $markdown .= sprintf("- [%s](%s)\n", $source['title'], $source['url']);
        }

        return $markdown;
    }

    /**
     * Format sources for API response with initial/expanded split
     *
     * Reorders sources to ensure webpages appear in initial results,
     * then splits into initial (shown) and expanded (hidden) groups.
     *
     * @param array<array{url: string, title: string, type?: string}> $sources
     * @return array{initial: array<int, array{url: string, title: string, type: string, index: int}>, expanded: array<int, array{url: string, title: string, type: string, index: int}>, hasMore: bool}
     */
    public function formatForApi(array $sources): array
    {
        if (empty($sources)) {
            return [
                'initial' => [],
                'expanded' => [],
                'hasMore' => false,
            ];
        }

        // Reorder to prioritize webpages in initial results
        $reordered = $this->reorderWithWebpagesPriority($sources);

        // Format all sources with index
        $formatted = [];
        foreach ($reordered as $index => $source) {
            $formatted[] = [
                'index' => $index + 1,
                'url' => $source['url'],
                'title' => $source['title'],
                'type' => $source['type'] ?? 'unknown',
            ];
        }

        // Split into initial and expanded
        $initial = array_slice($formatted, 0, self::INITIAL_SOURCES_COUNT);
        $expanded = array_slice($formatted, self::INITIAL_SOURCES_COUNT);

        return [
            'initial' => $initial,
            'expanded' => $expanded,
            'hasMore' => count($expanded) > 0,
        ];
    }

    /**
     * Reorder sources to ensure webpages appear in the initial display
     *
     * Strategy: Move up to MIN_WEBPAGES_IN_INITIAL webpages to the front,
     * while preserving relative order within each type group.
     *
     * @param array<array{url: string, title: string, type?: string}> $sources
     * @return array<array{url: string, title: string, type?: string}>
     */
    private function reorderWithWebpagesPriority(array $sources): array
    {
        $webpages = [];
        $others = [];

        // Separate webpages from other types
        foreach ($sources as $source) {
            if (($source['type'] ?? '') === 'webpage') {
                $webpages[] = $source;
            } else {
                $others[] = $source;
            }
        }

        // If no webpages or already enough in initial, return as-is
        if (count($webpages) === 0) {
            return $sources;
        }

        // Take up to MIN_WEBPAGES_IN_INITIAL webpages for priority placement
        $priorityWebpages = array_slice($webpages, 0, self::MIN_WEBPAGES_IN_INITIAL);
        $remainingWebpages = array_slice($webpages, self::MIN_WEBPAGES_IN_INITIAL);

        // Build reordered list:
        // 1. Priority webpages first
        // 2. Then other sources (PDFs)
        // 3. Then remaining webpages (if any)
        $reordered = array_merge($priorityWebpages, $others, $remainingWebpages);

        return $reordered;
    }
}
