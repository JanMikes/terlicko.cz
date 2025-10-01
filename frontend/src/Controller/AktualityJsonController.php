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
        $aktualityData = $this->content->getAktualityData(limit: 30);
        
        $jsonData = [];
        
        foreach ($aktualityData as $aktualita) {
            $category = '';
            if (!empty($aktualita->Tagy)) {
                $category = $aktualita->Tagy[0]->Tag;
            }

            $imageUrl = '';
            if ($aktualita->Obrazek !== null) {
                $baseUrl = $request->getSchemeAndHttpHost();
                $imageUrl = $baseUrl . $aktualita->Obrazek->url;
            }

            $detailUrl = '';
            if ($aktualita->slug !== null) {
                $detailUrl = $this->generateUrl('detail_aktuality', ['slug' => $aktualita->slug], UrlGeneratorInterface::ABSOLUTE_URL);
            }

            $baseUrl = $request->getSchemeAndHttpHost();
            $files = [];
            foreach ($aktualita->Soubory as $file) {
                $files[] = [
                    'file_title' => $file->caption ?? $file->name,
                    'file_url' => $baseUrl . $file->url,
                ];
            }

            $jsonData[] = [
                'title' => $aktualita->Nadpis,
                'perex' => $this->textProcessor->createPerex($aktualita->Popis),
                'category' => $category,
                'date_published' => $aktualita->DatumZverejneni->format('d.m.Y'),
                'author' => $aktualita->Zverejnil->Jmeno ?? '',
                'image' => $imageUrl,
                'href' => $detailUrl,
                'files' => $files,
                'content' => $this->textProcessor->markdownToHtml($aktualita->Popis),
            ];
        }
        
        return $this->json($jsonData);
    }
}
