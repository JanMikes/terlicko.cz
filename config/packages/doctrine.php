<?php

declare(strict_types=1);

use Ramsey\Uuid\Doctrine\UuidType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {

    $containerConfigurator->extension('doctrine', [
        'dbal' => [
            'url' => '%env(resolve:DATABASE_URL)%',
            'types' => [
                UuidType::NAME => UuidType::class,
            ],
        ],
        'orm' => [
            'report_fields_where_declared' => true,
            'auto_generate_proxy_classes' => true,
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
            'auto_mapping' => true,
            'controller_resolver' => [
                'auto_mapping' => false,
            ],
            'mappings' => [
                'TheDevs' => [
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/src/Entity',
                    'prefix' => 'Terlicko\\Web\\Entity',
                ],
            ],
        ],
    ]);
};
