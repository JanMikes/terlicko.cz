<?php

declare(strict_types=1);

return static function (\Symfony\Config\TwigConfig $twig): void {
    $twig->formThemes(['bootstrap_5_layout.html.twig']);

    $twig->date([
        'timezone' => 'Europe/Prague',
    ]);
};
