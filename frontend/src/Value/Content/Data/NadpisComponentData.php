<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type OdkazDataArray from OdkazData
 * @phpstan-type NadpisComponentDataArray array{
 *     Nadpis: string,
 *     Typ: string,
 *     Kotva: null|string,
 *     Odkaz: null|OdkazDataArray,
 * }
 */
readonly final class NadpisComponentData
{
    public function __construct(
        public string $Nadpis,
        public string $Typ,
        public null|string $Kotva,
        public null|OdkazData $Odkaz,
    ) {}

    /**
     * @param NadpisComponentDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Nadpis: $data['Nadpis'],
            Typ: $data['Typ'],
            Kotva: $data['Kotva'],
            Odkaz: $data['Odkaz'] !== null ? OdkazData::createFromStrapiResponse($data['Odkaz']) : null,
        );
    }
}

