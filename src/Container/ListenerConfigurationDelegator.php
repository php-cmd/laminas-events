<?php

declare(strict_types=1);

namespace PhpCmd\Event\Container;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\ServiceManager\Exception;
use PhpCmd\Event\Exception\InvalidServiceException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function get_debug_type;
use function gettype;
use function is_object;
use function sprintf;

class ListenerConfigurationDelegator
{
    private const DEFAULT_PRIORITY = 1;
    /**
     * Decorate an EventManager instance by attaching its listeners from configuration.
     * @param ContainerInterface $container
     * @param string $serviceName
     * @param callable $callback
     * @return EventManagerInterface
     * @throws InvalidServiceException If $callback produces something other than EventManagerInterface instance
     * @throws NotFoundExceptionInterface If $spec['listener'] is not found in the container
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $serviceName, callable $callback): EventManagerInterface
    {
        $eventManager = $callback();
        if (! $eventManager instanceof EventManager) {
            throw new InvalidServiceException(sprintf(
                'Delegator factory %s cannot operate on a %s; please map it only to the %s service',
                self::class,
                is_object($eventManager) ? $eventManager::class . ' instance' : gettype($eventManager),
                EventManager::class
            ));
        }

        if (! $container->has('config')) {
            return $eventManager;
        }

        $listeners = $container->get('config')['listeners'] ?? [];
        if ($listeners !== []) {
            foreach($listeners as $listener) {
                $listener = $container->get($listener);
                $listener->attach($eventManager);
            }
        }

        return $eventManager;
    }
}
