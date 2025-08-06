<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type OdkazDataArray from OdkazData
 * @phpstan-type TextovyOdkazDataArray array{
 *     Text: null|string,
 *     Odkaz: OdkazDataArray,
 * }
 */
readonly final class TextovyOdkazData
{
    /** @use CanCreateManyFromStrapiResponse<TextovyOdkazDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public null|string $Text,
        public OdkazData $Odkaz,
    ) {}

    /**
     * @param TextovyOdkazDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Text: $data['Text'],
            Odkaz: OdkazData::createFromStrapiResponse($data['Odkaz']),
        );
    }
}
