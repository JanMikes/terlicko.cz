<?php

declare(strict_types=1);

namespace Terlicko\Web\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Terlicko\Web\Services\Strapi\StrapiLinkHelper;
use Terlicko\Web\Value\Content\Data\OdkazData;

#[AsTwigComponent]
final class Odkaz
{
    public null|OdkazData $data = null;

    public function __construct(
        readonly private StrapiLinkHelper $strapiLinkHelper,
    ) {
    }

    public function getLink(): null|string
    {
        if ($this->data === null) {
            return null;
        }

        if ($this->data->sekceSlug !== null) {
            return $this->strapiLinkHelper->getLinkForSlug($this->data->sekceSlug);
        }

        return $this->data->url ?? '#';
    }
}
