# Middleware Guide

This guide covers the middleware components in the Laminas Events integration for CmdBus.

## Table of Contents

- [Overview](#overview)
- [PreHandleMiddleware](#prehandlemiddleware)
- [PostHandleMiddleware](#posthandlemiddleware)
- [Middleware Pipeline](#middleware-pipeline)
- [Creating Custom Middleware](#creating-custom-middleware)
- [Middleware Patterns](#middleware-patterns)

## Overview

The package provides two middleware components that integrate with the CmdBus middleware pipeline:

- **PreHandleMiddleware**: Triggers events before command execution
- **PostHandleMiddleware**: Triggers events after command execution

Both middleware implement the `PhpCmd\CmdBus\MiddlewareInterface` and use the `EventManagerAwareInterface` to access the event manager.

## PreHandleMiddleware

The `PreHandleMiddleware` triggers a `PreHandleEvent` before passing the command to the next handler in the pipeline.

### Implementation

```php
<?php

declare(strict_types=1);

namespace PhpCmd\Event\Middleware;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;
use PhpCmd\Event\PreHandleEvent;

final class PreHandleMiddleware implements
    MiddlewareInterface,
    EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        // Trigger pre-handle event
        $this->getEventManager()->triggerEvent(
            new PreHandleEvent($command)
        );

        // Continue to next handler
        return $handler->handle($command);
    }
}
```

### Configuration

The middleware is configured with high priority (100) to execute early in the pipeline:

```php
'middleware_pipeline' => [
    'pre_handle' => [
        'middleware' => PreHandleMiddleware::class,
        'priority' => 100,
    ],
],
```

### Usage Scenarios

#### 1. Validation Middleware

```php
// Listener for validation
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    $violations = $this->validator->validate($command);

    if (count($violations) > 0) {
        throw new ValidationException($violations);
    }
}
```

#### 2. Authorization Middleware

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    $requiredPermission = $command->getRequiredPermission();

    if (!$this->authService->hasPermission($requiredPermission)) {
        throw new ForbiddenException(
            "User lacks permission: {$requiredPermission}"
        );
    }
}
```

#### 3. Request Logging

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    $this->logger->info('Command received', [
        'command' => $command::class,
        'user' => $this->authService->getCurrentUserId(),
        'timestamp' => microtime(true),
    ]);
}
```

## PostHandleMiddleware

The `PostHandleMiddleware` triggers a `PostHandleEvent` after the command has been processed.

### PostHandleMiddleware Implementation

```php
<?php

declare(strict_types=1);

namespace PhpCmd\Event\Middleware;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;
use PhpCmd\CmdBus\Command\CommandResult;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;
use PhpCmd\Event\PostHandleEvent;

final class PostHandleMiddleware implements
    MiddlewareInterface,
    EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        // Execute command handler
        $result = $handler->handle($command);

        // Trigger post-handle event if result is CommandResult
        if ($result instanceof CommandResult) {
            $this->getEventManager()->triggerEvent(
                new PostHandleEvent($result)
            );

            // Return unwrapped result
            return $result->getResult();
        }

        return $result;
    }
}
```

### PostHandleMiddleware Configuration

The middleware is configured with low priority (-100) to execute late in the pipeline:

```php
'middleware_pipeline' => [
    'post_handle' => [
        'middleware' => PostHandleMiddleware::class,
        'priority' => -100,
    ],
],
```

### PostHandleMiddleware Usage Scenarios

#### 1. Success Logging

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $result = $event->getTarget();

    $this->logger->info('Command completed successfully', [
        'command' => $result->getCommand()::class,
        'duration' => $this->timer->getDuration(),
    ]);
}
```

#### 2. Event Publishing

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $result = $event->getTarget();
    $command = $result->getCommand();

    if ($command instanceof PublishesEvents) {
        foreach ($command->getEvents() as $domainEvent) {
            $this->eventBus->publish($domainEvent);
        }
    }
}
```

#### 3. Cache Updates

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $result = $event->getTarget();
    $command = $result->getCommand();

    if ($command instanceof CacheableCommand) {
        $this->cache->set(
            $command->getCacheKey(),
            $result->getResult(),
            $command->getTtl()
        );
    }
}
```

## Middleware Pipeline

The middleware pipeline determines the order of execution:

```text
Request → [Pre-Handle Middleware] → [Your Middleware] → Handler → [Your Middleware] → [Post-Handle Middleware] → Response
```

### Default Pipeline Order

```php
'middleware_pipeline' => [
    // High priority (executed first)
    'pre_handle' => [
        'middleware' => PreHandleMiddleware::class,
        'priority' => 100,
    ],

    // Your middleware here (priority 0-99)

    // Low priority (executed last)
    'post_handle' => [
        'middleware' => PostHandleMiddleware::class,
        'priority' => -100,
    ],
],
```

### Custom Pipeline Configuration

Insert your middleware at appropriate priorities:

```php
return [
    'PhpCmd\\CmdBus\\ConfigProvider' => [
        'middleware_pipeline' => [
            'pre_handle' => [
                'middleware' => PreHandleMiddleware::class,
                'priority' => 100,
            ],
            'authentication' => [
                'middleware' => AuthenticationMiddleware::class,
                'priority' => 90,
            ],
            'validation' => [
                'middleware' => ValidationMiddleware::class,
                'priority' => 80,
            ],
            'transaction' => [
                'middleware' => TransactionMiddleware::class,
                'priority' => 50,
            ],
            'post_handle' => [
                'middleware' => PostHandleMiddleware::class,
                'priority' => -100,
            ],
        ],
    ],
];
```

## Creating Custom Middleware

### Basic Structure

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;

final class CustomMiddleware implements MiddlewareInterface
{
    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        // Before command execution

        // Execute handler
        $result = $handler->handle($command);

        // After command execution

        return $result;
    }
}
```

### Event-Aware Custom Middleware

Integrate with the event system:

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;

final class MetricsMiddleware implements
    MiddlewareInterface,
    EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        $startTime = microtime(true);

        try {
            $result = $handler->handle($command);

            $this->recordMetrics($command, microtime(true) - $startTime, true);

            return $result;
        } catch (\Throwable $e) {
            $this->recordMetrics($command, microtime(true) - $startTime, false);
            throw $e;
        }
    }

    private function recordMetrics(
        CommandInterface $command,
        float $duration,
        bool $success
    ): void {
        $this->getEventManager()->trigger('metrics.recorded', null, [
            'command' => $command::class,
            'duration' => $duration,
            'success' => $success,
        ]);
    }
}
```

### Middleware with Dependencies

Use a factory for dependency injection:

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Log\LoggerInterface;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;

final class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        $this->logger->debug('Processing command', [
            'command' => $command::class,
        ]);

        try {
            $result = $handler->handle($command);

            $this->logger->debug('Command processed successfully');

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Command processing failed', [
                'error' => $e->getMessage(),
                'command' => $command::class,
            ]);

            throw $e;
        }
    }
}
```

Factory:

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LoggingMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): LoggingMiddleware
    {
        return new LoggingMiddleware(
            $container->get(LoggerInterface::class)
        );
    }
}
```

## Middleware Patterns

### Transaction Middleware

Wrap command execution in a database transaction:

```php
final class TransactionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection
    ) {}

    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        $this->connection->beginTransaction();

        try {
            $result = $handler->handle($command);
            $this->connection->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
```

### Retry Middleware

Implement retry logic for transient failures:

```php
final class RetryMiddleware implements MiddlewareInterface
{
    private const MAX_RETRIES = 3;

    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        $attempts = 0;

        while ($attempts < self::MAX_RETRIES) {
            try {
                return $handler->handle($command);
            } catch (TransientException $e) {
                $attempts++;

                if ($attempts >= self::MAX_RETRIES) {
                    throw $e;
                }

                usleep(100000 * $attempts); // Exponential backoff
            }
        }
    }
}
```

### Caching Middleware

Cache command results:

```php
final class CachingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CacheInterface $cache
    ) {}

    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        if (!$command instanceof CacheableCommand) {
            return $handler->handle($command);
        }

        $key = $command->getCacheKey();

        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $result = $handler->handle($command);

        $this->cache->set($key, $result, $command->getTtl());

        return $result;
    }
}
```

## Best Practices

1. **Single Responsibility**: Each middleware should have one clear purpose
2. **Order Matters**: Place middleware at appropriate priorities
3. **Pass Through**: Always call the next handler unless you have a reason not to
4. **Error Handling**: Catch and handle errors appropriately
5. **Dependency Injection**: Use factories for dependencies
6. **Documentation**: Document what each middleware does
7. **Testing**: Unit test middleware independently

## See Also

- [Configuration Guide](./configuration.md)
- [Events Reference](./events.md)
- [Best Practices](./best-practices.md)
- [Examples](./examples)
