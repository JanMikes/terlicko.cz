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

final class UredniDeskaJsonController extends AbstractController
{
    public function __construct(
        readonly private StrapiContent $content,
        readonly private TextProcessor $textProcessor,
    ) {}

    #[Route('/uredni-deska.json', name: 'uredni_deska_json')]
    public function __invoke(Request $request): JsonResponse
    {
        $uredniDeskyData = $this->content->getUredniDeskyData(shouldHideIfExpired: true);
        
        $jsonData = [];
        
        foreach ($uredniDeskyData as $uredniDeska) {
            $category = null;
            if (!empty($uredniDeska->Kategorie)) {
                $category = $uredniDeska->Kategorie[0]->Nazev;
            }
            
            $detailUrl = null;
            if ($uredniDeska->slug !== null) {
                $detailUrl = $this->generateUrl('detail_uredni_desky', ['slug' => $uredniDeska->slug], UrlGeneratorInterface::ABSOLUTE_URL);
            }
            
            $baseUrl = $request->getSchemeAndHttpHost();
            $files = [];
            foreach ($uredniDeska->Soubory as $file) {
                $files[] = [
                    'file_title' => $file->caption ?? $file->name,
                    'file_url' => $baseUrl . $file->url,
                    'file_data' => null,
                    'file_ext' => null,
                ];
            }
            
            $jsonData[] = [
                'title' => $uredniDeska->Nadpis,
                'perex' => $this->textProcessor->createPerex($uredniDeska->Popis ?? ''),
                'category' => $category,
                'date_published' => $uredniDeska->Datum_zverejneni->format('d.m.Y'),
                'date_valid' => $uredniDeska->Datum_stazeni?->format('d.m.Y'),
                'note' => null,
                'href' => $detailUrl,
                'files' => $files,
                'content' => $uredniDeska->Popis,
            ];
        }
        
        return $this->json($jsonData);
    }
}