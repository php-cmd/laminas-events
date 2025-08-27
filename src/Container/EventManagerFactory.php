<?php

declare(strict_types=1);

namespace PhpCmd\Event\Container;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Psr\Container\ContainerInterface;

use function assert;

final class EventManagerFactory
{
    public function __invoke(ContainerInterface $container): EventManager
    {
        $sharedEventManager = $container->get(SharedEventManagerInterface::class);
        assert($sharedEventManager instanceof SharedEventManagerInterface);
        return new EventManager($sharedEventManager);
    }
}
