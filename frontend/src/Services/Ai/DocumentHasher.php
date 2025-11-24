<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

readonly final class DocumentHasher
{
    /**
     * Generate content hash for change detection
     */
    public function hashContent(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Generate hash from file
     */
    public function hashFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException(sprintf('File not found: %s', $filePath));
        }

        return hash_file('sha256', $filePath) ?: throw new \RuntimeException('Failed to hash file');
    }

    /**
     * Generate hash from URL content
     */
    public function hashUrl(string $url): string
    {
        $content = @file_get_contents($url);

        if ($content === false) {
            throw new \RuntimeException(sprintf('Failed to fetch URL: %s', $url));
        }

        return $this->hashContent($content);
    }
}
