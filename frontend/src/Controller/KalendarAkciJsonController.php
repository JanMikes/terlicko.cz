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
use Terlicko\Web\Value\Content\Data\KalendarAkciData;

final class KalendarAkciJsonController extends AbstractController
{
    private const array CZECH_MONTHS = [
        1 => 'ledna', 2 => 'února', 3 => 'března', 4 => 'dubna',
        5 => 'května', 6 => 'června', 7 => 'července', 8 => 'srpna',
        9 => 'září', 10 => 'října', 11 => 'listopadu', 12 => 'prosince'
    ];

    private const array CZECH_DAYS = [
        1 => 'pondělí', 2 => 'úterý', 3 => 'středa', 4 => 'čtvrtek',
        5 => 'pátek', 6 => 'sobota', 7 => 'neděle'
    ];

    public function __construct(
        readonly private StrapiContent $content,
        readonly private TextProcessor $textProcessor,
    ) {}

    #[Route('/kalendar-akci.json', name: 'kalendar_akci_json')]
    public function __invoke(Request $request): JsonResponse
    {
        $kalendarAkciData = $this->content->getKalendarAkciData();
        
        $jsonData = [];
        
        foreach ($kalendarAkciData as $kalendarAkce) {
            $category = null;
            $perex = '';
            $content = '';
            $imageUrl = null;
            $detailUrl = null;
            
            if ($kalendarAkce->Aktualita !== null) {
                $perex = $this->textProcessor->createPerex($kalendarAkce->Aktualita->Popis);
                $content = $kalendarAkce->Aktualita->Popis;
                
                if (!empty($kalendarAkce->Aktualita->Tagy)) {
                    $category = $kalendarAkce->Aktualita->Tagy[0]->Tag;
                }
                
                if ($kalendarAkce->Aktualita->Obrazek !== null) {
                    $baseUrl = $request->getSchemeAndHttpHost();
                    $imageUrl = $baseUrl . $kalendarAkce->Aktualita->Obrazek->url;
                }
                
                if ($kalendarAkce->Aktualita->slug !== null) {
                    $detailUrl = $this->generateUrl('detail_aktuality', 
                        ['slug' => $kalendarAkce->Aktualita->slug], 
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                }
            }
            
            $actionDate = $this->formatActionDate($kalendarAkce->Datum, $kalendarAkce->DatumDo);
            $actionDateOrder = $kalendarAkce->Datum?->format('Y-m-d');
            $calendarUrl = $this->generateGoogleCalendarUrl($kalendarAkce);
            
            $jsonData[] = [
                'title' => $kalendarAkce->Nazev,
                'perex' => $perex,
                'category' => $category,
                'action_date' => $actionDate,
                'action_date_order' => $actionDateOrder,
                'organizer' => $kalendarAkce->Poradatel,
                'place' => null,
                'price' => null,
                'print_priority' => null,
                'image' => $imageUrl,
                'href' => $detailUrl,
                'action_calendar_url' => $calendarUrl,
                'content' => $content,
            ];
        }
        
        return $this->json($jsonData);
    }

    private function formatActionDate(?\DateTimeImmutable $datum, ?\DateTimeImmutable $datumDo): ?string
    {
        if ($datum === null) {
            return null;
        }

        $dayName = self::CZECH_DAYS[(int) $datum->format('N')];
        $day = $datum->format('j');
        $month = self::CZECH_MONTHS[(int) $datum->format('n')];
        $year = $datum->format('Y');
        $time = $datum->format('H:i');

        $formattedDate = "{$dayName} {$day}. {$month} {$year}";

        if ($time !== '00:00') {
            $formattedDate .= " od {$time}";
        }

        if ($datumDo !== null) {
            $endTime = $datumDo->format('H:i');
            if ($endTime !== '00:00') {
                $formattedDate .= " do {$endTime}";
            }
        }

        return $formattedDate;
    }

    private function generateGoogleCalendarUrl(KalendarAkciData $kalendarAkce): ?string
    {
        if ($kalendarAkce->Datum === null || $kalendarAkce->Nazev === null) {
            return null;
        }

        $startDate = $kalendarAkce->Datum->format('Ymd\THis\Z');
        $endDate = $kalendarAkce->DatumDo?->format('Ymd\THis\Z') ?? $kalendarAkce->Datum->modify('+2 hours')->format('Ymd\THis\Z');
        
        $params = [
            'action' => 'TEMPLATE',
            'text' => $kalendarAkce->Nazev,
            'dates' => $startDate . '/' . $endDate,
        ];

        if ($kalendarAkce->Aktualita?->Popis) {
            $params['details'] = strip_tags($kalendarAkce->Aktualita->Popis);
        }

        if ($kalendarAkce->Poradatel) {
            $params['location'] = $kalendarAkce->Poradatel;
        }

        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }
}
