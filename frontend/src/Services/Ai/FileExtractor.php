<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Ai;

use DateTimeImmutable;
use Terlicko\Web\Services\Strapi\StrapiApiClient;

/**
 * Extracts all PDF files from Strapi content for RAG indexing
 */
readonly final class FileExtractor
{
    public function __construct(
        private StrapiApiClient $strapiApiClient,
    ) {}

    /**
     * Extract all PDF files from Strapi upload directory
     *
     * @return array<array{
     *   url: string,
     *   name: string,
     *   caption: string|null,
     *   size: int,
     *   created_at: DateTimeImmutable
     * }>
     */
    public function extractAllPdfFiles(): array
    {
        // Get all files from Strapi upload API
        // Using pagination to get all files (max 100 per page)
        $allFiles = [];
        $start = 0;
        $limit = 100;

        do {
            /** @var array{data: array<array{id: int, name: string, alternativeText: string|null, caption: string|null, ext: string, mime: string, size: float, url: string, createdAt: string}>} $response */
            $response = $this->strapiApiClient->getApiResource(
                'upload/files',
                0,
                null,
                [
                    'ext' => ['$eq' => '.pdf'],
                ],
                [
                    'start' => $start,
                    'limit' => $limit,
                ],
            );

            $files = $response['data'];

            foreach ($files as $file) {
                $allFiles[] = [
                    'url' => $file['url'],
                    'name' => $file['name'],
                    'caption' => $file['caption'] ?? $file['alternativeText'] ?? null,
                    'size' => (int) ($file['size'] * 1024), // Convert KB to bytes
                    'created_at' => new DateTimeImmutable($file['createdAt']),
                ];
            }

            $start += $limit;

            // Continue if we got a full page (might be more)
        } while (count($files) === $limit);

        return $allFiles;
    }
}
