<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-type TelefonDataArray array{
 *      Telefon: string,
 *      Nazev_telefonu: null|string,
 *  }
 */
readonly final class TelefonData
{
    /** @use CanCreateManyFromStrapiResponse<TelefonDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Telefon,
        public null|string $NazevTelefonu,
    ) {}

    /**
     * @param TelefonDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Telefon: $data['Telefon'],
            NazevTelefonu: $data['Nazev_telefonu'],
        );
    }
}
