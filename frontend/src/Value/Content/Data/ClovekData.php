<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-type ClovekDataArray array{
 *     Jmeno: string,
 *     Email: string,
 *     Telefon: string,
 *     Pohlavi: string,
 *     Funkce: string,
 *     Fotka: ImageDataArray,
 *  }
 */
readonly final class ClovekData
{
    /** @use CanCreateManyFromStrapiResponse<ClovekDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Jmeno,
        public string $Funkce,
        public string|null $Email,
        public string|null $Telefon,
        public string $Pohlavi,
        public ImageData $Fotka,
    ) {
    }


    public function isMuz(): bool
    {
        return $this->Pohlavi === 'muz';
    }


    /**
     * @param ClovekDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            $data['Jmeno'],
            $data['Funkce'],
            $data['Email'],
            $data['Telefon'],
            $data['Pohlavi'],
            ImageData::createFromStrapiResponse($data['Fotka']),
        );
    }
}
