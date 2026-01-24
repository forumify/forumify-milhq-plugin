<?php

declare(strict_types=1);

namespace PluginTests;

use Forumify\Core\ForumifyKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

class PluginTestKernel extends ForumifyKernel
{
    use MicroKernelTrait {
        MicroKernelTrait::registerBundles as private registerSymfonyBundles;
    }

    public function __construct(string $env, bool $debug = false)
    {
        $context = ['APP_ENV' => $env, 'APP_DEBUG' => $debug];
        parent::__construct($context, dirname(__DIR__));
    }

    public function registerBundles(): iterable
    {
        yield from $this->registerSymfonyBundles();
    }
}
