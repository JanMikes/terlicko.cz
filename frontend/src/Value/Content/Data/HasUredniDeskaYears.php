<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

trait HasUredniDeskaYears
{
    /**
     * @param array<UredniDeskaData> $uredniDeska
     * @return array<int>
     */
    private static function getUredniDeskaYears(array $uredniDeska): array
    {
        $years = [];

        foreach ($uredniDeska as $dokument) {
            $year = (int) $dokument->Datum_zverejneni->format('Y');
            $years[$year] = $year;
        }

        sort($years);

        return count($years) > 1 ? $years : [];
    }
}
