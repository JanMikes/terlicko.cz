<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'http_method_override' => false,
        'csrf_protection' => true,
        'session' => [
            'handler_id' => PdoSessionHandler::class,
            'cookie_secure' => 'auto',
            'cookie_samesite' => 'lax',
            'cookie_lifetime' => 1345600,
            'gc_maxlifetime' => 1345600,
            'storage_factory_id' => 'session.storage.factory.native',
        ],
        'php_errors' => [
            'log' => true,
        ],
        'trusted_headers' => ['x-forwarded-for', 'x-forwarded-host', 'x-forwarded-proto', 'x-forwarded-port', 'x-forwarded-prefix'],
        'trusted_proxies' => '127.0.0.1,REMOTE_ADDR',
        'cache' => [
            'app' => 'cache.adapter.redis',
            'default_redis_provider' => 'redis://%env(REDIS_HOST)%:%env(REDIS_PORT)%',
        ],
        'rate_limiter' => [
            'ai_chat_messages' => [
                'policy' => 'sliding_window',
                'limit' => 10,
                'interval' => '1 minute',
            ],
            'ai_chat_daily' => [
                'policy' => 'sliding_window',
                'limit' => 100,
                'interval' => '1 day',
            ],
            'ai_new_conversations' => [
                'policy' => 'sliding_window',
                'limit' => 12,
                'interval' => '1 hour',
            ],
        ],
    ]);
};
