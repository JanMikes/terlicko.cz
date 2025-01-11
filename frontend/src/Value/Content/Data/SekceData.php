<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type SekceDataArray array{
 *      Nazev: string,
 *      Meta_description: string,
 *      Komponenty: array<mixed>,
 *  }
 */
readonly final class SekceData
{
    /**
     * @param array<mixed> $Komponenty
     */
    public function __construct(
        public string $Nazev,
        public string|null $Meta_description,
        public array $Komponenty,
    ) {
    }

    /**
     * @param SekceDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Nazev'],
            $data['Meta_description'],
            $data['Komponenty'],
        );
    }
}
