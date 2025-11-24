<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller\Ai;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Terlicko\Web\Services\Strapi\StrapiContent;

#[Route('/ai/content.json', name: 'ai_content_json')]
final class AiContentJsonController extends AbstractController
{
    public function __construct(
        private readonly StrapiContent $strapiContent,
    ) {
    }

    public function __invoke(): Response
    {
        // Fetch all aktuality (news) pages
        $aktuality = $this->strapiContent->getAktualityData();

        $normalizedContent = [];

        foreach ($aktuality as $item) {
            $url = '/aktuality/' . $item->slug;
            $title = $item->Nadpis;
            $content = $item->Popis;

            $normalizedContent[] = [
                'url' => $url,
                'title' => $title,
                'type' => 'aktuality',
                'content' => [
                    'format' => 'text',
                    'normalized_text' => $content,
                ],
                'created_at' => $item->DatumZverejneni->format(\DateTimeInterface::ATOM),
            ];
        }

        return new JsonResponse([
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'count' => count($normalizedContent),
            'items' => $normalizedContent,
        ]);
    }
}
