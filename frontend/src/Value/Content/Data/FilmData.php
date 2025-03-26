<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type FilmDataArray array{
 *      Jmeno: string,
 *  }
 */
readonly final class FilmData
{
    /** @use CanCreateManyFromStrapiResponse<VizitkaDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Jmeno,
    ) {}

    /**
     * @param FilmDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Jmeno: $data['Jmeno'],
        );
    }
}
