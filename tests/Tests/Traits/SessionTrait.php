<?php

declare(strict_types=1);

namespace PluginTests\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

trait SessionTrait
{
    use RequiresContainerTrait;

    private function initializeSession(): void
    {
        $session = new Session(new MockFileSessionStorage());
        $request = new Request();
        $request->setSession($session);
        $stack = self::getContainer()->get(RequestStack::class);
        $stack->push($request);
    }
}
