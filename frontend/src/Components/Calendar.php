<?php

declare(strict_types=1);

namespace Terlicko\Web\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use DateTimeImmutable;
use Terlicko\Web\Services\Strapi\StrapiContent;
use Terlicko\Web\Value\Content\Data\DatumData;
use Terlicko\Web\Value\Content\Data\KalendarAkciData;

#[AsLiveComponent]
final class Calendar
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $year = 0;

    #[LiveProp]
    public int $month = 0;

    #[LiveProp]
    public ?int $selectedDay = null;

    public function __construct(
        readonly private StrapiContent $strapi,
    ) {
    }

    public function mount(): void
    {
        $today = new DateTimeImmutable('now', new \DateTimeZone('Europe/Prague'));
        $this->year = (int) $today->format('Y');
        $this->month = (int) $today->format('n');
        
        // Select today as initial day when viewing current month
        $this->selectedDay = (int) $today->format('j');
    }

    #[LiveAction]
    public function previousMonth(): void
    {
        if ($this->month === 1) {
            $this->month = 12;
            $this->year--;
        } else {
            $this->month--;
        }

        $this->selectedDay = null;
    }

    #[LiveAction]
    public function nextMonth(): void
    {
        if ($this->month === 12) {
            $this->month = 1;
            $this->year++;
        } else {
            $this->month++;
        }

        $this->selectedDay = null;
    }

    #[LiveAction]
    public function changeDay(#[LiveArg] int $day): void
    {
        $this->selectedDay = $day;
    }

    /**
     * @return array<int, array<KalendarAkciData>>
     */
    public function getEvents(): array
    {
        $events = $this->strapi->getKalendarAkciData(year: $this->year, month: $this->month);
        $perDay = [];

        foreach ($events as $event) {
            if ($event->Datum === null) {
                continue;
            }

            $startDate = $event->Datum;
            $endDate = $event->DatumDo ?? $event->Datum;

            $currentDate = $startDate;
            while ($currentDate <= $endDate) {
                if ($currentDate->format('Y') == $this->year && $currentDate->format('n') == $this->month) {
                    $perDay[(int)$currentDate->format('j')][] = $event;
                }
                $currentDate = $currentDate->modify('+1 day');
            }
        }

        return $perDay;
    }
}
