<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type ImageDataArray from ImageData
 * @phpstan-type ClovekDataArray array{
 *     Jmeno: string,
 *     Email: null|string,
 *     Telefon: null|string,
 *     Pohlavi: string,
 *     Fotka: null|ImageDataArray,
 *  }
 */
readonly final class ClovekData
{
    /** @use CanCreateManyFromStrapiResponse<ClovekDataArray> */
    use CanCreateManyFromStrapiResponse;

    public function __construct(
        public string $Jmeno,
        public null|string $Email,
        public null|string $Telefon,
        public string $Pohlavi,
        public null|ImageData $Fotka,
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
            $data['Email'],
            $data['Telefon'],
            $data['Pohlavi'],
            $data['Fotka'] !== null ? ImageData::createFromStrapiResponse($data['Fotka']) : null,
        );
    }
}
