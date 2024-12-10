<?php

declare(strict_types=1);

use Symfony\Config\SecurityConfig;

return static function (SecurityConfig $securityConfig): void {
    $securityConfig->firewall('dev')
        ->pattern('^/(_profiler|_wdt|css|images|js|assets)/')
        ->security(false);

    $securityConfig->firewall('stateless')
        ->pattern('^(/-/health-check|/media/cache|/sitemap)')
        ->stateless(true)
        ->security(false);

    $mainFirewall = $securityConfig->firewall('main')
        ->lazy(true)
        ->stateless(true)
        ->security(false);
};
