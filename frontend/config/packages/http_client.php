<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (FrameworkConfig $framework): void {
    $httpClientConfig = $framework->httpClient();

    $httpClientConfig->scopedClient('strapi.client')
        ->authBearer(param('strapi.api_key'))
        ->baseUri(param('strapi.api_uri'))
        ->header('Accept', 'application/json');
};
