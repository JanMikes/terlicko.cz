<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type KartaTipNaVyletDataArray from KartaTipNaVyletData
 */
readonly final class TipyNaVyletComponentData
{
    public function __construct(
        /** @var array<KartaTipNaVyletData> */
        public array $Karty
    ) {}

    /**
     * @param array{
     *     Karty: null|array<KartaTipNaVyletDataArray>,
     *  } $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Karty: KartaTipNaVyletData::createManyFromStrapiResponse($data['Karty'] ?? []),
        );
    }
}
