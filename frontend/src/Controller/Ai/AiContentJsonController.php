<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller\Ai;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Ai\ContentNormalizer;
use Terlicko\Web\Services\Strapi\StrapiContent;

#[Route('/ai/content.json', name: 'ai_content_json')]
final class AiContentJsonController extends AbstractController
{
    public function __construct(
        private readonly ContentNormalizer $contentNormalizer,
        private readonly StrapiContent $strapiContent,
    ) {
    }

    public function __invoke(): Response
    {
        // Fetch all aktuality (news) pages
        $aktuality = $this->strapiContent->getAktualityData();

        $normalizedContent = [];

        foreach ($aktuality['data'] as $item) {
            $url = '/aktuality/' . $item['attributes']['slug'];
            $title = $item['attributes']['title'];
            $content = '';

            // Normalize blocks to markdown
            if (isset($item['attributes']['blocks']) && is_array($item['attributes']['blocks'])) {
                $content = $this->contentNormalizer->normalizeBlocks($item['attributes']['blocks']);
            }

            $normalizedContent[] = [
                'url' => $url,
                'title' => $title,
                'type' => 'aktuality',
                'content' => $content,
                'created_at' => $item['attributes']['createdAt'] ?? null,
                'updated_at' => $item['attributes']['updatedAt'] ?? null,
            ];
        }

        return new JsonResponse([
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'count' => count($normalizedContent),
            'pages' => $normalizedContent,
        ]);
    }
}
