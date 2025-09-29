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
            $category = '';
            $perex = '';
            $content = '';
            $imageUrl = '';
            $detailUrl = '';
            
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
            $actionDateOrder = ($kalendarAkce->DatumDo ?? $kalendarAkce->Datum)?->format('Y-m-d');
            $calendarUrl = $this->generateGoogleCalendarUrl($kalendarAkce);
            
            $jsonData[] = [
                'title' => $kalendarAkce->Nazev ?? '',
                'perex' => $perex,
                'category' => $category,
                'action_date' => $actionDate ?? '',
                'action_date_order' => $actionDateOrder ?? '',
                'organizer' => $kalendarAkce->Poradatel ?? '',
                'image' => $imageUrl,
                'href' => $detailUrl,
                'action_calendar_url' => $calendarUrl ?? '',
                'content' => $this->textProcessor->markdownToHtml($content),
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
        $startDate = $datum->format('j.n.Y');
        $time = $datum->format('H:i');

        $formattedDate = $dayName . ' ' . $startDate;

        if ($time !== '00:00') {
            $formattedDate .= ' od ' . $time;
        }

        if ($datumDo !== null) {
            $endTime = $datumDo->format('H:i');
            $endDate = $datumDo->format('j.n.Y');

            // Check if it's a multiday event
            if ($datum->format('Y-m-d') !== $datumDo->format('Y-m-d')) {
                $endDayName = self::CZECH_DAYS[(int) $datumDo->format('N')];
                if ($endTime !== '00:00') {
                    $formattedDate .= ' do ' . $endDayName . ' ' . $endDate . ' ' . $endTime;
                } else {
                    $formattedDate .= ' do ' . $endDayName . ' ' . $endDate;
                }
            } else {
                // Same day event
                if ($endTime !== '00:00') {
                    $formattedDate .= ' do ' . $endTime;
                }
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
