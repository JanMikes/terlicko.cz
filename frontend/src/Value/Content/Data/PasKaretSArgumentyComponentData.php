<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type KartaSArgumentyDataArray from KartaSArgumentyData
 */
readonly final class PasKaretSArgumentyComponentData
{
    public function __construct(
        /** @var array<KartaSArgumentyData> */
        public array $Karty
    ) {}

    /**
     * @param array{
     *     Karty: null|array<KartaSArgumentyDataArray>,
     *  } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Karty: KartaSArgumentyData::createManyFromStrapiResponse($data['Karty'] ?? []),
        );
    }
}
