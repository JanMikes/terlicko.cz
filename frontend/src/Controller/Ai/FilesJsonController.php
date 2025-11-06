<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller\Ai;

use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Terlicko\Web\Services\Ai\FileExtractor;

/**
 * Provides all PDF files from Strapi for RAG indexing
 */
final class FilesJsonController extends AbstractController
{
    public function __construct(
        readonly private FileExtractor $fileExtractor,
        readonly private CacheInterface $cache,
        readonly private ClockInterface $clock,
    ) {}

    #[Route('/ai/files.json', name: 'ai_files_json')]
    public function __invoke(Request $request): JsonResponse
    {
        $data = $this->cache->get('ai_files_json', function (ItemInterface $item) use ($request) {
            // Cache for 6 hours
            $item->expiresAfter(3600 * 6);

            $baseUrl = $request->getSchemeAndHttpHost();
            $files = $this->fileExtractor->extractAllPdfFiles();

            $items = [];
            foreach ($files as $file) {
                $items[] = [
                    'id' => md5($file['url']),
                    'title' => $file['caption'] ?? $file['name'],
                    'source_url' => $baseUrl . $file['url'],
                    'type' => 'pdf',
                    'language' => 'cs',
                    'published_at' => $file['created_at']->format('c'),
                    'size_bytes' => $file['size'],
                    'meta' => [
                        'filename' => $file['name'],
                    ],
                ];
            }

            return [
                'items' => $items,
                'meta' => [
                    'total' => count($items),
                    'generated_at' => $this->clock->now()->format('c'),
                ],
            ];
        });

        return $this->json($data);
    }
}
