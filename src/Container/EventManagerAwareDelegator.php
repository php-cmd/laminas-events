<?php

declare(strict_types=1);

namespace PhpCmd\Event\Container;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Psr\Container\ContainerInterface;

use function assert;

final class EventManagerAwareDelegator
{
    public function __invoke(
        ContainerInterface $container,
        string $serviceName,
        callable $callback
    ): EventManagerAwareInterface {
        // call services __invoke method to get an instance
        $service = $callback();
        // include a duck-type for the method name provided by the interface, ie if they just used the trait
        if ($service instanceof EventManagerAwareInterface) {
            $eventManager = $container->get(EventManagerInterface::class);
            assert($eventManager instanceof EventManagerInterface);
            $service->setEventManager($eventManager);
        }

        assert($service instanceof EventManagerAwareInterface);
        return $service;
    }
}
