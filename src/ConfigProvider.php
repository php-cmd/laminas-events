<?php

declare(strict_types=1);

namespace PhpCmd\Event;

use Laminas\EventManager;
use PhpCmd\CmdBus\ConfigProvider as BusProvider;

/**
 * Configuration provider for cmd-bus event manager integration
 */
final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies'     => $this->getDependencies(),
            BusProvider::class => [
                BusProvider::MIDDLEWARE_PIPELINE_KEY => $this->getMiddleware(),
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases' => [
                EventManager\EventManagerInterface::class       => EventManager\EventManager::class,
                'EventManager'                                  => EventManager\EventManager::class,
                EventManager\SharedEventManagerInterface::class => EventManager\SharedEventManager::class,
                'SharedEventManager'                            => EventManager\SharedEventManager::class,
            ],
            'delegators' => [
                EventManager\EventManager::class => [
                    Container\ListenerConfigurationDelegator::class,
                ],
                Middleware\PostHandleMiddleware::class => [
                    Container\EventManagerAwareDelegator::class,
                ],
                Middleware\PreHandleMiddleware::class => [
                    Container\EventManagerAwareDelegator::class,
                ],
            ],
            'factories' => [
                EventManager\EventManager::class => Container\EventManagerFactory::class,
                //EventManager\SharedEventManager::class => static fn() => new EventManager\SharedEventManager(),
            ],
            'invokables' => [
                EventManager\SharedEventManager::class => EventManager\SharedEventManager::class,
                Middleware\PostHandleMiddleware::class => Middleware\PostHandleMiddleware::class,
                Middleware\PreHandleMiddleware::class  => Middleware\PreHandleMiddleware::class,
            ],
        ];
    }

    public function getMiddleware(): array
    {
        return [
            [
                'middleware' => Middleware\PreHandleMiddleware::class,
                'priority'   => 100,
            ],
            [
                'middleware' => Middleware\PostHandleMiddleware::class,
                'priority'   => -100,
            ],
        ];
    }
}
