<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type FilmDataArray from FilmData
 */
readonly final class ProgramKinaComponentData
{
    public function __construct(
        /** @var array<FilmData> */
        public array $Filmy,
    ) {}

    /**
     * @param array<FilmDataArray> $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Filmy: FilmData::createManyFromStrapiResponse($data['Filmy']),
        );
    }
}
