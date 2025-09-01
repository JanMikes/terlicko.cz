<?php

declare(strict_types=1);

namespace Terlicko\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Services\TextProcessor;

final class AktualityJsonController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content,
        readonly private TextProcessor $textProcessor,
    ) {}

    #[Route('/aktuality.json', name: 'aktuality_json')]
    public function __invoke(Request $request): JsonResponse
    {
        $aktualityData = $this->content->getAktualityData();
        
        $jsonData = [];
        
        foreach ($aktualityData as $aktualita) {
            $category = null;
            if (!empty($aktualita->Tagy)) {
                $category = $aktualita->Tagy[0]->Tag;
            }
            
            $imageUrl = null;
            if ($aktualita->Obrazek !== null) {
                $baseUrl = $request->getSchemeAndHttpHost();
                $imageUrl = $baseUrl . $aktualita->Obrazek->url;
            }
            
            $detailUrl = null;
            if ($aktualita->slug !== null) {
                $detailUrl = $this->generateUrl('detail_aktuality', ['slug' => $aktualita->slug], UrlGeneratorInterface::ABSOLUTE_URL);
            }
            
            $jsonData[] = [
                'title' => $aktualita->Nadpis,
                'perex' => $this->textProcessor->createPerex($aktualita->Popis),
                'category' => $category,
                'date_published' => $aktualita->DatumZverejneni->format('d.m.Y'),
                'author' => $aktualita->Zverejnil?->Jmeno,
                'image' => $imageUrl,
                'href' => $detailUrl,
                'content' => $aktualita->Popis,
            ];
        }
        
        return $this->json($jsonData);
    }
}
