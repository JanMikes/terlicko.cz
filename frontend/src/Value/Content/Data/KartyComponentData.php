<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type KartaDataArray from KartaData
 */
readonly final class KartyComponentData
{
    public function __construct(
        /** @var array<KartaData> */
        public array $Karty,
    ) {}

    /**
     * @param array{Karty: array<KartaDataArray>} $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Karty: KartaData::createManyFromStrapiResponse($data['Karty']),
        );
    }
}
