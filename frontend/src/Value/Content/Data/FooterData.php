<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type TextovyOdkazDataArray from TextovyOdkazData
 * @phpstan-import-type ObrazekDataArray from ObrazekData
 * @phpstan-type FooterDataArray array{
 *     Kontakt: null|string,
 *     Uredni_hodiny: null|string,
 *     Bannery: array<ObrazekDataArray>,
 *     Odkazy: array<TextovyOdkazDataArray>,
 * }
 */
readonly final class FooterData
{
    /**
     * @param array<ObrazekData> $Bannery
     * @param array<TextovyOdkazData> $Odkazy
     */
    public function __construct(
        public null|string $Kontakt,
        public null|string $Uredni_hodiny,
        public array $Bannery,
        public array $Odkazy,
    ) {
    }

    /**
     * @param FooterDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Kontakt: $data['Kontakt'],
            Uredni_hodiny: $data['Uredni_hodiny'],
            Bannery: ObrazekData::createManyFromStrapiResponse($data['Bannery']),
            Odkazy: TextovyOdkazData::createManyFromStrapiResponse($data['Odkazy']),
        );
    }
}
