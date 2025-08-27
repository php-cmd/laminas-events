<?php

declare(strict_types=1);

namespace PhpCmd\Event\Container;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\Exception\InvalidServiceException;
use Psr\Container\ContainerInterface;

use function get_debug_type;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;

class ListenerConfigurationDelegator
{
    private const DEFAULT_PRIORITY = 1;

    /**
     * Decorate an EventManager instance by attaching its listeners from configuration.
     */
    public function __invoke(
        ContainerInterface $container,
        string $serviceName,
        callable $callback
    ): EventManagerInterface {
        $eventManager = $callback();
        if (! $eventManager instanceof EventManager) {
            throw new InvalidServiceException(sprintf(
                'Delegator factory %s cannot operate on a %s; please map it only to the %s service',
                self::class,
                is_object($eventManager) ? $eventManager::class . ' instance' : get_debug_type($eventManager),
                EventManager::class
            ));
        }

        if (! $container->has('config')) {
            return $eventManager;
        }

        $this->attachListeners($container, $eventManager);

        return $eventManager;
    }

    private function attachListeners(ContainerInterface $container, EventManagerInterface $eventManager): void
    {
        /** @var array{listeners?: list<array{listener?: mixed, priority?: int, event?: string|array<string>}>} $config */
        $config = $container->get('config');

        foreach ($config['listeners'] ?? [] as $spec) {
            $listener = $spec['listener'] ?? null;
            $priority = $spec['priority'] ?? self::DEFAULT_PRIORITY;

            if (is_string($listener) && $container->has($listener) && ! is_callable($listener)) {
                $listener = $container->get($listener);
                if ($listener instanceof AbstractListenerAggregate) {
                    $listener->attach($eventManager, $priority);
                }
                continue;
            } elseif (is_callable($listener) && isset($spec['event'])) {
                if (is_array($spec['event'])) {
                    foreach ($spec['event'] as $event) {
                        $eventManager->attach($event, $listener, $priority);
                    }
                } else {
                    $eventManager->attach($spec['event'], $listener, $priority);
                }
            }
        }
    }
}
