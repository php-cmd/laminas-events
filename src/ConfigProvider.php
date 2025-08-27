<?php

declare(strict_types=1);

namespace PhpCmd\Event;

use Laminas\EventManager;
use PhpCmd\CmdBus\ConfigProvider as BusProvider;

/**
 * @phpstan-import-type ServiceManagerConfiguration from BusProvider
 * @phpstan-import-type CmdBusConfig from BusProvider
 * @phpstan-import-type MiddlewarePipeSpec from BusProvider
 * @phpstan-import-type CommandMap from BusProvider
 */
final class ConfigProvider
{
    /**
     * @phpstan-return array{
     *     dependencies: ServiceManagerConfiguration
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies'     => $this->getDependencies(),
            BusProvider::class => [
                BusProvider::COMMAND_MAP_KEY         => $this->getCommandMap(),
                BusProvider::MIDDLEWARE_PIPELINE_KEY => $this->getMiddleware(),
            ],
        ];
    }

    /**
     * @phpstan-return ServiceManagerConfiguration
     */
    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                EventManager\EventManagerInterface::class       => EventManager\EventManager::class,
                'EventManager'                                  => EventManager\EventManager::class,
                EventManager\SharedEventManagerInterface::class => EventManager\SharedEventManager::class,
                'SharedEventManager'                            => EventManager\SharedEventManager::class,
            ],
            'delegators' => [
                EventManager\EventManager::class       => [
                    Container\ListenerConfigurationDelegator::class,
                ],
                Middleware\PostHandleMiddleware::class => [
                    Container\EventManagerAwareDelegator::class,
                ],
                Middleware\PreHandleMiddleware::class  => [
                    Container\EventManagerAwareDelegator::class,
                ],
            ],
            'factories'  => [
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

    /**
     * @phpstan-return MiddlewarePipeSpec
     */
    public function getMiddleware(): array
    {
        return [
            'pre_handle'  => [
                'middleware' => Middleware\PreHandleMiddleware::class,
                'priority'   => 100,
            ],
            'post_handle' => [
                'middleware' => Middleware\PostHandleMiddleware::class,
                'priority'   => -100,
            ],
        ];
    }

    /**
     * @phpstan-return CommandMap
     */
    public function getCommandMap(): array
    {
        return [
            // Command FQCN => CommandHandler FQCN
        ];
    }
}
