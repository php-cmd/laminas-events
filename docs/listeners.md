# Listener Configuration

This guide covers how to configure and work with event listeners in the Laminas Events integration for CmdBus.

## Table of Contents

- [Overview](#overview)
- [Listener Types](#listener-types)
- [Registering Listeners](#registering-listeners)
- [Listener Priorities](#listener-priorities)
- [Listener Aggregates](#listener-aggregates)
- [Callable Listeners](#callable-listeners)
- [Best Practices](#best-practices)

## Overview

Listeners are the components that respond to events triggered by the middleware. They can perform actions before or after command execution, such as logging, validation, notification, or caching.

## Listener Types

### 1. Listener Aggregates

Extend `AbstractListenerAggregate` for multiple event listeners in one class:

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\PreHandleEvent;
use PhpCmd\Event\PostHandleEvent;

final class CommandAuditListener extends AbstractListenerAggregate
{
    public function __construct(
        private readonly AuditLoggerInterface $auditLogger
    ) {}

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            PreHandleEvent::NAME,
            [$this, 'logPreHandle'],
            $priority
        );

        $this->listeners[] = $events->attach(
            PostHandleEvent::NAME,
            [$this, 'logPostHandle'],
            $priority
        );
    }

    public function logPreHandle(PreHandleEvent $event): void
    {
        $command = $event->getTarget();

        $this->auditLogger->log('command.started', [
            'command' => $command::class,
            'timestamp' => time(),
        ]);
    }

    public function logPostHandle(PostHandleEvent $event): void
    {
        $this->auditLogger->log('command.completed', [
            'timestamp' => time(),
        ]);
    }
}
```

### 2. Callable Listeners

Simple functions or invokable classes:

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use PhpCmd\Event\PreHandleEvent;

final class SimpleValidator
{
    public function __invoke(PreHandleEvent $event): void
    {
        $command = $event->getTarget();

        // Simple validation logic
        if (!$this->isValid($command)) {
            throw new \InvalidArgumentException('Invalid command');
        }
    }

    private function isValid($command): bool
    {
        // Validation logic
        return true;
    }
}
```

### 3. Service Listeners

Listeners retrieved from the container:

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Log\LoggerInterface;
use PhpCmd\Event\PreHandleEvent;

final class LoggingListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(PreHandleEvent $event): void
    {
        $command = $event->getTarget();

        $this->logger->info('Command received', [
            'command' => $command::class,
        ]);
    }
}
```

## Registering Listeners

### Configuration File

Register listeners in your application configuration:

```php
<?php

declare(strict_types=1);

// config/autoload/listeners.global.php

return [
    'dependencies' => [
        'factories' => [
            \App\Listener\CommandAuditListener::class =>
                \App\Listener\CommandAuditListenerFactory::class,
            \App\Listener\LoggingListener::class =>
                \App\Listener\LoggingListenerFactory::class,
        ],
    ],
    'listeners' => [
        [
            'listener' => \App\Listener\CommandAuditListener::class,
            'priority' => 100,
        ],
        [
            'listener' => \App\Listener\LoggingListener::class,
            'priority' => 90,
        ],
    ],
];
```

### Factory Pattern

Create factories for listeners with dependencies:

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Container\ContainerInterface;

final class CommandAuditListenerFactory
{
    public function __invoke(ContainerInterface $container): CommandAuditListener
    {
        return new CommandAuditListener(
            $container->get(AuditLoggerInterface::class)
        );
    }
}
```

### Programmatic Registration

Register listeners programmatically:

```php
<?php

use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\PreHandleEvent;

$eventManager = $container->get(EventManagerInterface::class);

$eventManager->attach(
    PreHandleEvent::NAME,
    function (PreHandleEvent $event) {
        // Handle event
    },
    100 // Priority
);
```

## Listener Priorities

Priorities determine the execution order of listeners. Higher numbers execute first.

### Default Priority

If not specified, the default priority is `1`:

```php
return [
    'listeners' => [
        [
            'listener' => \App\Listener\MyListener::class,
            // priority defaults to 1
        ],
    ],
];
```

### Priority Guidelines

| Priority Range | Purpose | Example |
|----------------|---------|---------|
| 1000+ | Critical pre-processing | Authentication, Rate limiting |
| 100-999 | Normal pre-processing | Validation, Authorization |
| 1-99 | Standard processing | Logging, Metrics |
| 0 | Default | General listeners |
| -1 to -99 | Post-processing | Cache updates, Cleanup |
| -100 to -999 | Final processing | Notifications, Event broadcasting |
| <-1000 | Guaranteed last | Final logging, Monitoring |

### Example Priority Configuration

```php
return [
    'listeners' => [
        // Authentication - must run first
        [
            'listener' => \App\Listener\AuthenticationListener::class,
            'priority' => 1000,
        ],
        // Authorization - after authentication
        [
            'listener' => \App\Listener\AuthorizationListener::class,
            'priority' => 900,
        ],
        // Validation - before execution
        [
            'listener' => \App\Listener\ValidationListener::class,
            'priority' => 800,
        ],
        // Logging - standard priority
        [
            'listener' => \App\Listener\LoggingListener::class,
            'priority' => 100,
        ],
        // Metrics - lower priority
        [
            'listener' => \App\Listener\MetricsListener::class,
            'priority' => 50,
        ],
        // Notifications - after execution
        [
            'listener' => \App\Listener\NotificationListener::class,
            'priority' => -100,
        ],
    ],
];
```

## Listener Aggregates

Listener aggregates allow grouping related listeners in a single class.

### Creating an Aggregate

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\PreHandleEvent;
use PhpCmd\Event\PostHandleEvent;

final class UserCommandListener extends AbstractListenerAggregate
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly NotifierInterface $notifier
    ) {}

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        // Pre-handle validation
        $this->listeners[] = $events->attach(
            PreHandleEvent::NAME,
            [$this, 'validateUserCommand'],
            $priority + 10 // Higher priority for validation
        );

        // Pre-handle authorization
        $this->listeners[] = $events->attach(
            PreHandleEvent::NAME,
            [$this, 'authorizeUserCommand'],
            $priority
        );

        // Post-handle notification
        $this->listeners[] = $events->attach(
            PostHandleEvent::NAME,
            [$this, 'notifyUserAction'],
            $priority
        );
    }

    public function validateUserCommand(PreHandleEvent $event): void
    {
        $command = $event->getTarget();

        if ($command instanceof UserCommandInterface) {
            if (!$this->userService->exists($command->getUserId())) {
                throw new \RuntimeException('User not found');
            }
        }
    }

    public function authorizeUserCommand(PreHandleEvent $event): void
    {
        $command = $event->getTarget();

        if ($command instanceof UserCommandInterface) {
            if (!$this->userService->canExecute($command)) {
                throw new \RuntimeException('Not authorized');
            }
        }
    }

    public function notifyUserAction(PostHandleEvent $event): void
    {
        $result = $event->getTarget();

        if ($result instanceof UserActionResult) {
            $this->notifier->notify(
                $result->getUserId(),
                'Action completed successfully'
            );
        }
    }
}
```

### Detaching Aggregate Listeners

```php
// Detach all listeners in the aggregate
$aggregate->detach($eventManager);
```

## Callable Listeners

### Inline Functions

Register simple callbacks directly:

```php
return [
    'listeners' => [
        [
            'listener' => function ($event) {
                error_log('Command executed: ' . $event->getTarget()::class);
            },
            'event' => \PhpCmd\Event\PreHandleEvent::NAME,
            'priority' => 100,
        ],
    ],
];
```

### Invokable Classes

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use PhpCmd\Event\PreHandleEvent;

final class InvokableListener
{
    public function __invoke(PreHandleEvent $event): void
    {
        // Handle event
    }
}
```

Configuration:

```php
return [
    'dependencies' => [
        'invokables' => [
            \App\Listener\InvokableListener::class =>
                \App\Listener\InvokableListener::class,
        ],
    ],
    'listeners' => [
        [
            'listener' => \App\Listener\InvokableListener::class,
            'event' => \PhpCmd\Event\PreHandleEvent::NAME,
            'priority' => 100,
        ],
    ],
];
```

### Static Methods

```php
return [
    'listeners' => [
        [
            'listener' => [\App\Listener\StaticListener::class, 'handle'],
            'event' => \PhpCmd\Event\PreHandleEvent::NAME,
            'priority' => 100,
        ],
    ],
];
```

## Advanced Patterns

### Conditional Listeners

Execute listeners based on conditions:

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    // Only process specific commands
    if (!$command instanceof LoggableCommand) {
        return;
    }

    $this->logger->info('Loggable command', [
        'command' => $command::class,
    ]);
}
```

### Listener with State

Track state across multiple events:

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\PreHandleEvent;
use PhpCmd\Event\PostHandleEvent;

final class TimingListener extends AbstractListenerAggregate
{
    private array $timings = [];

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            PreHandleEvent::NAME,
            [$this, 'startTimer'],
            $priority
        );

        $this->listeners[] = $events->attach(
            PostHandleEvent::NAME,
            [$this, 'stopTimer'],
            $priority
        );
    }

    public function startTimer(PreHandleEvent $event): void
    {
        $commandId = spl_object_hash($event->getTarget());
        $this->timings[$commandId] = microtime(true);
    }

    public function stopTimer(PostHandleEvent $event): void
    {
        $result = $event->getTarget();
        $command = $result?->getCommand();

        if ($command) {
            $commandId = spl_object_hash($command);
            $duration = microtime(true) - ($this->timings[$commandId] ?? 0);

            echo "Command took {$duration} seconds\n";

            unset($this->timings[$commandId]);
        }
    }
}
```

### Multiple Event Registration

Register one listener for multiple events:

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

## Best Practices

### 1. Single Responsibility

Each listener should handle one specific concern:

```php
// Good - focused responsibility
final class ValidationListener
{
    public function __invoke(PreHandleEvent $event): void
    {
        $this->validate($event->getTarget());
    }
}

// Bad - multiple responsibilities
final class MixedListener
{
    public function __invoke(PreHandleEvent $event): void
    {
        $this->validate($event->getTarget());
        $this->log($event->getTarget());
        $this->notify($event->getTarget());
    }
}
```

### 2. Use Type Hints

Always type-hint event parameters:

```php
// Good
public function onPreHandle(PreHandleEvent $event): void
{
    // ...
}

// Bad
public function onPreHandle($event): void
{
    // ...
}
```

### 3. Handle Errors Gracefully

```php
public function onPreHandle(PreHandleEvent $event): void
{
    try {
        $this->riskyOperation($event->getTarget());
    } catch (\Throwable $e) {
        $this->logger->error('Listener failed', [
            'error' => $e->getMessage(),
        ]);

        // Decide: re-throw or continue
        // throw $e;
    }
}
```

### 4. Use Dependency Injection

```php
// Good - dependencies injected
final class MyListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}
}

// Bad - static dependencies
final class BadListener
{
    public function __invoke($event): void
    {
        Logger::log('...'); // Global state
    }
}
```

### 5. Document Listener Purpose

```php
/**
 * Validates user permissions before command execution.
 *
 * Checks if the current user has the required permissions
 * to execute the command. Throws ForbiddenException if not.
 */
final class AuthorizationListener
{
    // ...
}
```

### 6. Test Independently

```php
final class ValidationListenerTest extends TestCase
{
    public function testValidCommandPasses(): void
    {
        $listener = new ValidationListener($this->validator);
        $event = new PreHandleEvent(new ValidCommand());

        $listener($event);

        // Should not throw
        $this->assertTrue(true);
    }

    public function testInvalidCommandThrows(): void
    {
        $this->expectException(ValidationException::class);

        $listener = new ValidationListener($this->validator);
        $event = new PreHandleEvent(new InvalidCommand());

        $listener($event);
    }
}
```

## See Also

- [Events Reference](./events.md)
- [Configuration Guide](./configuration.md)
- [Best Practices](./best-practices.md)
- [Examples](./examples)
