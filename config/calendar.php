<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults();

    $services
        ->autowire()
        ->autoconfigure()
        ->load('Forumify\\Milhq\\Calendar\\', dirname(__DIR__) . '/src/Calendar/');
};
