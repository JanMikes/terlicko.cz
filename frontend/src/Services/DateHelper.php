<?php

declare(strict_types=1);

namespace Terlicko\Web\Services;

final readonly class DateHelper
{
    private const array CZECH_DAYS = [
        1 => 'pondělí', 2 => 'úterý', 3 => 'středa', 4 => 'čtvrtek',
        5 => 'pátek', 6 => 'sobota', 7 => 'neděle'
    ];

    public static function formatActionDate(?\DateTimeImmutable $datum, ?\DateTimeImmutable $datumDo): ?string
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
                // Same day event - always show end time if provided
                if ($endTime !== '00:00') {
                    $formattedDate .= ' do ' . $endTime;
                }
            }
        }

        return $formattedDate;
    }
}