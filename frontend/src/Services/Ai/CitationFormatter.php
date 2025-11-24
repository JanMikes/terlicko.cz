<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

readonly final class CitationFormatter
{
    /**
     * Format sources as JSON citations
     *
     * @param array<array{url: string, title: string}> $sources
     */
    public function formatAsJson(array $sources): string
    {
        return json_encode($sources, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Format sources as markdown list
     *
     * @param array<array{url: string, title: string}> $sources
     */
    public function formatAsMarkdown(array $sources): string
    {
        if (empty($sources)) {
            return '';
        }

        $markdown = "**Zdroje:**\n\n";
        foreach ($sources as $index => $source) {
            $markdown .= sprintf("- [%s](%s)\n", $source['title'], $source['url']);
        }

        return $markdown;
    }

    /**
     * Format sources for API response
     *
     * @param array<array{url: string, title: string}> $sources
     * @return array<int, array{url: string, title: string, index: int}>
     */
    public function formatForApi(array $sources): array
    {
        $formatted = [];
        foreach ($sources as $index => $source) {
            $formatted[] = [
                'index' => $index + 1,
                'url' => $source['url'],
                'title' => $source['title'],
            ];
        }
        return $formatted;
    }
}
