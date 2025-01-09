<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

final class UzemniData
{
    use HasUredniDeskaYears;

    public function __construct(
        public readonly string $Nadpis,

        /**
         * @var array<UredniDeskaData> $Uredni_deska
         */
        public readonly array $Uredni_deska,

        /**
         * @var array<KategoriePlanyData> $Uredni_deska
         */
        public readonly array $Kategorie,

        /**
         * @var array<int>
         */
        public readonly array $Uredni_deska_roky,
    ) {}


    /**
     * @param array<UredniDeskaData> $uredniDeska
     */
    public static function createFromStrapiResponse(mixed $data, array $uredniDeska): self
    {
        return new self(
            $data['Nadpis'],
            $uredniDeska,
            KategoriePlanyData::createManyFromStrapiResponse($data['Kategorie']),
            self::getUredniDeskaYears($uredniDeska),
        );
    }
}
