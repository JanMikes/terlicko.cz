<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class YoutubeExtension extends AbstractExtension
{
    /**
     * @return array<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('youtube_embed_url', $this->getYoutubeEmbedUrl(...)),
        ];
    }

    public function getYoutubeEmbedUrl(string $url): ?string
    {
        $videoId = $this->extractVideoId($url);

        if ($videoId === null) {
            return null;
        }

        return 'https://www.youtube.com/embed/' . $videoId;
    }

    private function extractVideoId(string $url): ?string
    {
        // https://www.youtube.com/watch?v=VIDEO_ID
        if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        // https://youtu.be/VIDEO_ID
        if (preg_match('#youtu\.be/([a-zA-Z0-9_-]{11})#', $url, $matches)) {
            return $matches[1];
        }

        // https://www.youtube.com/embed/VIDEO_ID
        if (preg_match('#youtube\.com/embed/([a-zA-Z0-9_-]{11})#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
