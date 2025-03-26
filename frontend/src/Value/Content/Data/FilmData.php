<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-import-type DatumDataArray from DatumData
 * @phpstan-type FilmDataArray array{
 *     Film: string,
 *     Vstupne: string,
 *     Popis: null|string,
 *     Obrazek: null|ImageDataArray,
 *     Datumy: array<DatumDataArray>,
 *  }
 */
readonly final class FilmData
{
    /** @use CanCreateManyFromStrapiResponse<FilmDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Film,
        public string $Vstupne,
        public null|string $Popis,
        public null|ImageData $Obrazek,
        /** @var array<DatumData> */
        public array $Datumy,
    ) {}

    /**
     * @param FilmDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Film: $data['Film'],
            Vstupne: $data['Vstupne'],
            Popis: $data['Popis'],
            Obrazek: $data['Obrazek'] !== null ? ImageData::createFromStrapiResponse($data['Obrazek']) : null,
            Datumy: DatumData::createManyFromStrapiResponse($data['Datumy']),
        );
    }
}
