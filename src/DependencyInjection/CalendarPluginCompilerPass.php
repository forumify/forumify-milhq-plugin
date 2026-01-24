<?php

declare(strict_types=1);

namespace Forumify\Milhq\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Forumify\Calendar\ForumifyCalendarPlugin;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CalendarPluginCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!class_exists(ForumifyCalendarPlugin::class)) {
            return;
        }

        $mappingDir = __DIR__ . '/../../config/doctrine/calendar';
        $pass = DoctrineOrmMappingsPass::createXmlMappingDriver([
            $mappingDir => 'Forumify\Milhq\Entity',
        ]);
        $pass->process($container);
    }
}
