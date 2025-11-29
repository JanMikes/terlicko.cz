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
        // Get all PDF files from Strapi upload API
        /** @var array<array{id: int, name: string, alternativeText: string|null, caption: string|null, ext: string, mime: string, size: float, url: string, createdAt: string}> $files */
        $files = $this->strapiApiClient->getApiResource(
            'upload/files',
            0,
            null,
            [
                'ext' => ['$eq' => '.pdf'],
            ],
        );

        $allFiles = [];
        foreach ($files as $file) {
            $allFiles[] = [
                'url' => $file['url'],
                'name' => $file['name'],
                'caption' => $file['caption'] ?? $file['alternativeText'] ?? null,
                'size' => (int) ($file['size'] * 1024), // Convert KB to bytes
                'created_at' => new DateTimeImmutable($file['createdAt']),
            ];
        }

        return $allFiles;
    }

    /**
     * Extract all image files from Strapi upload directory
     *
     * @return array<array{
     *   url: string,
     *   name: string,
     *   caption: string|null,
     *   size: int,
     *   created_at: DateTimeImmutable,
     *   ext: string
     * }>
     */
    public function extractAllImageFiles(): array
    {
        $allFiles = [];

        // Supported image extensions
        $extensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];

        foreach ($extensions as $ext) {
            /** @var array<array{id: int, name: string, alternativeText: string|null, caption: string|null, ext: string, mime: string, size: float, url: string, createdAt: string}> $files */
            $files = $this->strapiApiClient->getApiResource(
                'upload/files',
                0,
                null,
                [
                    'ext' => ['$eq' => $ext],
                ],
            );

            foreach ($files as $file) {
                $allFiles[] = [
                    'url' => $file['url'],
                    'name' => $file['name'],
                    'caption' => $file['caption'] ?? $file['alternativeText'] ?? null,
                    'size' => (int) ($file['size'] * 1024), // Convert KB to bytes
                    'created_at' => new DateTimeImmutable($file['createdAt']),
                    'ext' => $file['ext'],
                ];
            }
        }

        return $allFiles;
    }
}
