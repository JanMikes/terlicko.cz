<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type TerminAkceDataArray from TerminAkceData
 */
readonly final class TerminyAkciComponentData
{
    public function __construct(
        /** @var array<TerminAkceData> */
        public array $Terminy,
    ) {}

    /**
     * @param array{Terminy: array<TerminAkceDataArray>} $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Terminy: TerminAkceData::createManyFromStrapiResponse($data['Terminy']),
        );
    }
}
