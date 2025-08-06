<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/**
 * @phpstan-import-type SlideDataArray from SlideData
 * @phpstan-import-type OdkazDataArray from OdkazData
 * @phpstan-import-type HomepageRychlyOdkazDataArray from HomepageRychlyOdkazData
 * @phpstan-import-type HomekageKartaDataArray from HomekageKartaData
 * @phpstan-type HomepageDataArray array{
 *     Slider: array<SlideDataArray>,
 *     Rychle_odkazy: array<HomepageRychlyOdkazDataArray>,
 *     Tipy_a_aktivity: array<HomekageKartaDataArray>,
 *     Vsechny_tipy_a_aktivity_odkaz: null|OdkazDataArray
 *  }
 */
readonly final class HomepageData
{
    /**
     * @param array<SlideData> $Slider
     * @param array<HomepageRychlyOdkazData> $RychleOdkazy
     * @param array<HomekageKartaData> $Tipy
     */
    public function __construct(
        public array $Slider,
        public array $RychleOdkazy,
        public array $Tipy,
        public null|OdkazData $TipyOdkaz,
    ) {}

    /**
     * @param HomepageDataArray $data
     */
    public static function createFromStrapiResponse(array $data): self
    {
        return new self(
            Slider: SlideData::createManyFromStrapiResponse($data['Slider']),
            RychleOdkazy: HomepageRychlyOdkazData::createManyFromStrapiResponse($data['Rychle_odkazy']),
            Tipy: HomekageKartaData::createManyFromStrapiResponse($data['Tipy_a_aktivity']),
            TipyOdkaz: $data['Vsechny_tipy_a_aktivity_odkaz'] !== null ? OdkazData::createFromStrapiResponse($data['Vsechny_tipy_a_aktivity_odkaz']) : null,
        );
    }
}
