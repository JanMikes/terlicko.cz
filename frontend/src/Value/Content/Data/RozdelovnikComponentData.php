<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type RozdelovnikComponentDataArray array{
 *     Tloustka_cary: string,
 *     Typ: string,
 * }
 */
readonly final class RozdelovnikComponentData
{
    public function __construct(
        public string $Tloustka_cary,
        public string $Typ,
    ) {}

    /**
     * @param RozdelovnikComponentDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Tloustka_cary'],
            $data['Typ'],
        );
    }
}
