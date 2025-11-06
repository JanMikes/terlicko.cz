<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller\Ai;

use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Terlicko\Web\Services\Ai\ContentNormalizer;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Services\Strapi\StrapiLinkHelper;

/**
 * Provides all section content normalized to markdown for RAG indexing
 */
final class ContentJsonController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $strapiContent,
        readonly private ContentNormalizer $contentNormalizer,
        readonly private CacheInterface $cache,
        readonly private ClockInterface $clock,
        readonly private StrapiLinkHelper $strapiLinkHelper,
    ) {}

    #[Route('/ai/content.json', name: 'ai_content_json')]
    public function __invoke(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', '10');
        $offset = (int) $request->query->get('offset', '0');

        // Ensure reasonable limits
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $cacheKey = sprintf('ai_content_json_%d_%d', $offset, $limit);

        $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($limit, $offset) {
            // Cache for 6 hours
            $item->expiresAfter(3600 * 6);

            // Get all section slugs first
            $allSections = $this->strapiContent->getSectionSlugs();
            $sectionSlugs = array_keys($allSections);
            $total = count($sectionSlugs);

            // Apply pagination
            $paginatedSlugs = array_slice($sectionSlugs, $offset, $limit);

            $items = [];
            foreach ($paginatedSlugs as $slug) {
                try {
                    $section = $this->strapiContent->getSekceData($slug);
                    $urlPath = trim($this->strapiLinkHelper->getLinkForSlug($slug), '/');

                    // Normalize all components to markdown
                    $normalizedText = '';
                    foreach ($section->Komponenty as $component) {
                        $normalizedText .= $this->contentNormalizer->normalizeComponent($component);
                    }

                    // Build absolute URL
                    $url = $this->generateUrl('section', ['path' => $urlPath], UrlGeneratorInterface::ABSOLUTE_URL);


                    $items[] = [
                        'id' => $slug,
                        'url' => $url,
                        'canonical_url' => $url,
                        'title' => $section->Nazev,
                        'language' => 'cs',
                        'updated_at' => $this->clock->now()->format('c'), // Sections don't have updated_at
                        'checksum' => md5($normalizedText),
                        'content' => [
                            'format' => 'markdown',
                            'normalized_text' => trim($normalizedText),
                        ],
                        'meta' => [
                            'description' => $section->Meta_description,
                            'parent_slug' => $section->parentSlug,
                            'components_count' => count($section->Komponenty),
                        ],
                    ];
                } catch (\Exception $e) {
                    // Skip sections that fail to load
                    continue;
                }
            }

            return [
                'items' => $items,
                'meta' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'returned' => count($items),
                    'generated_at' => $this->clock->now()->format('c'),
                ],
            ];
        });

        return $this->json($data);
    }
}
