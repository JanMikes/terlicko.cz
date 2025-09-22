<?php

declare(strict_types=1);

namespace Terlicko\Web\Services\Twig;

use DateTimeInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class CzechDateExtension extends AbstractExtension
{
    private const array CZECH_MONTHS = [
        1 => 'led',
        2 => 'úno',
        3 => 'bře',
        4 => 'dub',
        5 => 'kvě',
        6 => 'čvn',
        7 => 'čvc',
        8 => 'srp',
        9 => 'zář',
        10 => 'říj',
        11 => 'lis',
        12 => 'pro',
    ];

    /**
     * @return array<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('czech_month', $this->getCzechMonth(...)),
        ];
    }

    public function getCzechMonth(DateTimeInterface $date): string
    {
        $monthNumber = (int) $date->format('n');

        return self::CZECH_MONTHS[$monthNumber];
    }
}
