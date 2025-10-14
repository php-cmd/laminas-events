# Configuration Guide

This guide covers all configuration options available in the Laminas Events integration for CmdBus.

## Table of Contents

- [Overview](#overview)
- [ConfigProvider](#configprovider)
- [Service Configuration](#service-configuration)
- [Middleware Configuration](#middleware-configuration)
- [Listener Configuration](#listener-configuration)
- [Event Manager Configuration](#event-manager-configuration)
- [Advanced Configuration](#advanced-configuration)

## Overview

The package uses Laminas ConfigAggregator pattern for configuration. All configuration is handled through the `PhpCmd\Event\ConfigProvider` class, which provides sensible defaults that work out of the box.

## ConfigProvider

The `ConfigProvider` class returns a configuration array with the following structure:

```php
return [
    'dependencies' => [
        'aliases' => [...],
        'factories' => [...],
        'delegators' => [...],
        'invokables' => [...],
    ],
    'PhpCmd\\CmdBus\\ConfigProvider' => [
        'command_map' => [...],
        'middleware_pipeline' => [...],
    ],
];
```

### Default Dependencies

The provider registers these services automatically:

```php
'aliases' => [
    // EventManager alias
    EventManagerInterface::class => EventManager::class,
    'EventManager' => EventManager::class,

    // SharedEventManager alias
    SharedEventManagerInterface::class => SharedEventManager::class,
    'SharedEventManager' => SharedEventManager::class,
],
'factories' => [
    EventManager::class => Container\EventManagerFactory::class,
],
'invokables' => [
    SharedEventManager::class => SharedEventManager::class,
    Middleware\PostHandleMiddleware::class => Middleware\PostHandleMiddleware::class,
    Middleware\PreHandleMiddleware::class => Middleware\PreHandleMiddleware::class,
],
'delegators' => [
    EventManager::class => [
        Container\ListenerConfigurationDelegator::class,
    ],
    Middleware\PostHandleMiddleware::class => [
        Container\EventManagerAwareDelegator::class,
    ],
    Middleware\PreHandleMiddleware::class => [
        Container\EventManagerAwareDelegator::class,
    ],
],
```

## Service Configuration

### Custom EventManager

If you need a custom EventManager implementation:

```php
// config/autoload/events.global.php
return [
    'dependencies' => [
        'factories' => [
            \Laminas\EventManager\EventManager::class => MyCustomEventManagerFactory::class,
        ],
    ],
];
```

### Custom SharedEventManager

To use a custom SharedEventManager:

```php
return [
    'dependencies' => [
        'factories' => [
            \Laminas\EventManager\SharedEventManagerInterface::class => MySharedEventManagerFactory::class,
        ],
    ],
];
```

### Service Aliases

You can create additional aliases for convenience:

```php
return [
    'dependencies' => [
        'aliases' => [
            'my.event.manager' => \Laminas\EventManager\EventManagerInterface::class,
        ],
    ],
];
```

## Middleware Configuration

The package registers two middleware components in the CmdBus pipeline:

### PreHandleMiddleware

Executes before the command handler:

```php
'middleware_pipeline' => [
    'pre_handle' => [
        'middleware' => Middleware\PreHandleMiddleware::class,
        'priority' => 100, // High priority - runs early
    ],
],
```

### PostHandleMiddleware

Executes after the command handler:

```php
'middleware_pipeline' => [
    'post_handle' => [
        'middleware' => Middleware\PostHandleMiddleware::class,
        'priority' => -100, // Low priority - runs late
    ],
],
```

### Custom Middleware Priority

Adjust priorities to control execution order:

```php
return [
    'PhpCmd\\CmdBus\\ConfigProvider' => [
        'middleware_pipeline' => [
            'pre_handle' => [
                'middleware' => \PhpCmd\Event\Middleware\PreHandleMiddleware::class,
                'priority' => 200, // Execute earlier
            ],
            'custom_middleware' => [
                'middleware' => \App\CustomMiddleware::class,
                'priority' => 150,
            ],
            'post_handle' => [
                'middleware' => \PhpCmd\Event\Middleware\PostHandleMiddleware::class,
                'priority' => -200, // Execute later
            ],
        ],
    ],
];
```

## Listener Configuration

Listeners are configured through the `listeners` configuration key.

### Basic Listener Registration

```php
return [
    'listeners' => [
        [
            'listener' => \App\Listener\MyListener::class,
            'priority' => 100,
        ],
    ],
];
```

### Multiple Listeners

Register multiple listeners with different priorities:

```php
return [
    'listeners' => [
        [
            'listener' => \App\Listener\LoggerListener::class,
            'priority' => 100, // Runs first
        ],
        [
            'listener' => \App\Listener\ValidationListener::class,
            'priority' => 90, // Runs second
        ],
        [
            'listener' => \App\Listener\NotificationListener::class,
            'priority' => 80, // Runs third
        ],
    ],
];
```

### Callable Listeners

Register callable listeners for specific events:

```php
return [
    'listeners' => [
        [
            'listener' => function ($event) {
                // Handle event inline
            },
            'event' => \PhpCmd\Event\PreHandleEvent::NAME,
            'priority' => 100,
        ],
    ],
];
```

### Multiple Events per Listener

Attach a single listener to multiple events:

```php
return [
    'listeners' => [
        [
            'listener' => \App\Listener\MultiEventListener::class,
            'event' => [
                \PhpCmd\Event\PreHandleEvent::NAME,
                \PhpCmd\Event\PostHandleEvent::NAME,
            ],
            'priority' => 100,
        ],
    ],
];
```

### Listener Aggregates

For `AbstractListenerAggregate` implementations:

```php
return [
    'dependencies' => [
        'factories' => [
            \App\Listener\MyAggregate::class => \App\Listener\MyAggregateFactory::class,
        ],
    ],
    'listeners' => [
        [
            'listener' => \App\Listener\MyAggregate::class,
            'priority' => 100,
        ],
    ],
];
```

The aggregate's `attach()` method will be called automatically.

## Event Manager Configuration

### Shared Event Manager

The SharedEventManager allows different EventManager instances to share listeners:

```php
use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManagerInterface;

$sharedEvents = $container->get(SharedEventManagerInterface::class);

// Attach to shared events
$sharedEvents->attach(
    'MyEventIdentifier',
    'my.event',
    function ($event) {
        // Handle event
    }
);

// Create EventManager with shared events
$events = new EventManager($sharedEvents);
```

### Event Identifiers

Set identifiers for your event managers:

```php
$eventManager = $container->get(EventManagerInterface::class);
$eventManager->setIdentifiers([
    'MyApp',
    'CommandBus',
    MyClass::class,
]);
```

## Advanced Configuration

### Lazy Loading Listeners

Listeners are lazy-loaded from the container:

```php
return [
    'dependencies' => [
        'factories' => [
            \App\Listener\ExpensiveListener::class => function ($container) {
                // Only instantiated when needed
                return new ExpensiveListener(
                    $container->get(ExpensiveService::class)
                );
            },
        ],
    ],
    'listeners' => [
        [
            'listener' => \App\Listener\ExpensiveListener::class,
            'priority' => 100,
        ],
    ],
];
```

### Environment-Specific Configuration

Use different configurations per environment:

```php
// config/autoload/events.local.php (development)
return [
    'listeners' => [
        [
            'listener' => \App\Listener\DebugListener::class,
            'priority' => 1000, // Highest priority for debugging
        ],
    ],
];

// config/autoload/events.production.php (production)
return [
    'listeners' => [
        [
            'listener' => \App\Listener\PerformanceListener::class,
            'priority' => 100,
        ],
    ],
];
```

### Conditional Listener Registration

Register listeners conditionally:

```php
return [
    'listeners' => array_filter([
        [
            'listener' => \App\Listener\AlwaysListener::class,
            'priority' => 100,
        ],
        getenv('ENABLE_LOGGING') ? [
            'listener' => \App\Listener\LoggerListener::class,
            'priority' => 90,
        ] : null,
        isset($_ENV['ENABLE_METRICS']) ? [
            'listener' => \App\Listener\MetricsListener::class,
            'priority' => 80,
        ] : null,
    ]),
];
```

### Overriding Default Configuration

Override the package defaults in your application configuration:

```php
// config/autoload/events.global.php
use PhpCmd\Event\ConfigProvider;

return [
    'dependencies' => [
        'delegators' => [
            \Laminas\EventManager\EventManager::class => [
                // Add your delegator before the package's
                \App\Container\MyCustomDelegator::class,
                \PhpCmd\Event\Container\ListenerConfigurationDelegator::class,
            ],
        ],
    ],
];
```

## Configuration Examples

### Complete Application Configuration

Here's a complete example for a real application:

```php
<?php

declare(strict_types=1);

// config/autoload/cmdbus.global.php

use PhpCmd\CmdBus\ConfigProvider as BusProvider;
use PhpCmd\Event\ConfigProvider as EventProvider;

$busConfig = (new BusProvider())();
$eventConfig = (new EventProvider())();

return [
    'dependencies' => array_merge_recursive(
        $busConfig['dependencies'] ?? [],
        $eventConfig['dependencies'] ?? [],
        [
            'factories' => [
                \App\Listener\AuditLogger::class => \App\Listener\AuditLoggerFactory::class,
                \App\Listener\MetricsCollector::class => \App\Listener\MetricsCollectorFactory::class,
            ],
        ]
    ),

    BusProvider::class => array_merge_recursive(
        $busConfig[BusProvider::class] ?? [],
        $eventConfig[BusProvider::class] ?? [],
        [
            'command_map' => [
                \App\Command\CreateUser::class => \App\Handler\CreateUserHandler::class,
                \App\Command\UpdateUser::class => \App\Handler\UpdateUserHandler::class,
            ],
        ]
    ),

    'listeners' => [
        [
            'listener' => \App\Listener\AuditLogger::class,
            'priority' => 100,
        ],
        [
            'listener' => \App\Listener\MetricsCollector::class,
            'priority' => 90,
        ],
    ],
];
```

## See Also

- [Getting Started](./getting-started.md)
- [Listener Configuration](./listeners.md)
- [Middleware Guide](./middleware.md)
- [Best Practices](./best-practices.md)
