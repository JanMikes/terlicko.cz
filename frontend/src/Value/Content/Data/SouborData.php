<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type FileDataArray from FileData
 * @phpstan-type SouborDataArray array{
 *     Nadpis: string,
 *     Soubor: FileDataArray|null,
 * }
 */
readonly final class SouborData
{
    /** @use CanCreateManyFromStrapiResponse<SouborDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Nadpis,
        public ?FileData $Soubor,
    ) {
    }

    /**
     * @param SouborDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Nadpis'],
            $data['Soubor'] !== null ? FileData::createFromStrapiResponse($data['Soubor']) : null,
        );
    }
}
