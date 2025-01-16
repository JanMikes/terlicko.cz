<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ObrazekDataArray from ObrazekData
 */
readonly final class ObrazekComponentData
{
    /**
     * @param array<ObrazekData> $Obrazek
     */
    public function __construct(
        public array $Obrazek,
    ) {}

    /**
     * @param array{
     *     Obrazek: array<ObrazekDataArray>,
     *  } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            ObrazekData::createManyFromStrapiResponse($data['Obrazek']),
        );
    }
}
