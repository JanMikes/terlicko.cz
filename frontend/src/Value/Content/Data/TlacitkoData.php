<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type TlacitkoDataArray array{
 *     Text: string,
 *     Odkaz: string,
 *     Styl: string,
 * }
 */
readonly final class TlacitkoData
{
    /** @use CanCreateManyFromStrapiResponse<TlacitkoDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Text,
        public string $Odkaz,
        public string $Styl,
    ) {}

    /**
     * @param TlacitkoDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Text'],
            $data['Odkaz'],
            $data['Styl'],
        );
    }
}
