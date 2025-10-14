# Events Reference

This document provides a comprehensive reference for all events in the Laminas Events integration for CmdBus.

## Table of Contents

- [Overview](#overview)
- [Event Lifecycle](#event-lifecycle)
- [PreHandleEvent](#prehandleevent)
- [PostHandleEvent](#posthandleevent)
- [Event Properties](#event-properties)
- [Working with Events](#working-with-events)
- [Event Propagation](#event-propagation)

## Overview

The package provides two main events that are triggered during command execution:

1. **PreHandleEvent** - Triggered before command execution
2. **PostHandleEvent** - Triggered after command execution

Both events extend `Laminas\EventManager\Event` and provide access to the command and its context.

## Event Lifecycle

The event flow in the command bus execution:

```text
┌─────────────────────────────────────────────────────────────┐
│                    CmdBus->handle($command)                 │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│           PreHandleMiddleware (priority: 100)               │
│  ┌───────────────────────────────────────────────────────┐  │
│  │  EventManager->trigger(new PreHandleEvent($command))  │  │
│  │       ↓                                               │  │
│  │  [Listener 1] priority: 100                           │  │
│  │  [Listener 2] priority: 90                            │  │
│  │  [Listener 3] priority: 80                            │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   CommandHandler->handle()                  │
│                    [Your Business Logic]                    │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│          PostHandleMiddleware (priority: -100)              │
│  ┌───────────────────────────────────────────────────────┐  │
│  │  EventManager->trigger(new PostHandleEvent($result))  │  │
│  │       ↓                                               │  │
│  │  [Listener 1] priority: 100                           │  │
│  │  [Listener 2] priority: 90                            │  │
│  │  [Listener 3] priority: 80                            │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
                      Return Result
```

## PreHandleEvent

The `PreHandleEvent` is triggered before the command handler processes the command.

### Class Definition

```php
namespace PhpCmd\Event;

use Laminas\EventManager\Event;
use PhpCmd\CmdBus\Command\NamedCommandInterface;
use PhpCmd\CmdBus\CommandInterface;

final class PreHandleEvent extends Event
{
    public const NAME = 'pre.handle';

    public function __construct(CommandInterface $target, ?array $params = [])
    {
        parent::__construct(self::NAME, $target, $params);
    }

    public function getTarget(): CommandInterface|NamedCommandInterface
    {
        return $this->target;
    }
}
```

### Properties

- **NAME**: `'pre.handle'` - The event identifier
- **Target**: The command object being executed
- **Params**: Additional parameters (optional)

### Usage

#### Listening to PreHandleEvent

```php
use PhpCmd\Event\PreHandleEvent;
use Laminas\EventManager\EventManagerInterface;

$eventManager->attach(
    PreHandleEvent::NAME,
    function (PreHandleEvent $event) {
        $command = $event->getTarget();
        // Your pre-handle logic
    },
    100 // Priority
);
```

#### Common Use Cases

##### 1. Validation

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    if (!$this->validator->validate($command)) {
        throw new ValidationException(
            'Command validation failed',
            $this->validator->getErrors()
        );
    }
}
```

##### 2. Authorization

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    if (!$this->authService->canExecute($command)) {
        throw new UnauthorizedException(
            'User not authorized to execute this command'
        );
    }
}
```

##### 3. Logging

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();
    $commandName = $command instanceof NamedCommandInterface
        ? $command->getName()
        : $command::class;

    $this->logger->info('Executing command', [
        'command' => $commandName,
        'user_id' => $this->authService->getUserId(),
        'timestamp' => time(),
    ]);
}
```

##### 4. Metrics Collection

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    $this->metrics->increment('commands.executed');
    $this->metrics->histogram(
        'command.type',
        ['type' => $command::class]
    );
}
```

##### 5. Caching

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    if ($command instanceof CacheableCommandInterface) {
        $cacheKey = $command->getCacheKey();

        if ($this->cache->has($cacheKey)) {
            // Stop propagation and return cached result
            $event->stopPropagation(true);
            $event->setParam('result', $this->cache->get($cacheKey));
        }
    }
}
```

## PostHandleEvent

The `PostHandleEvent` is triggered after the command handler has processed the command.

### PostHandleEvent Class Definition

```php
namespace PhpCmd\Event;

use Laminas\EventManager\Event;
use PhpCmd\CmdBus\Command\CommandResultInterface;

final class PostHandleEvent extends Event
{
    public const NAME = 'post.handle';

    public function __construct(?CommandResultInterface $target = null, ?array $params = [])
    {
        parent::__construct(self::NAME, $target, $params);
    }
}
```

### PostHandleEvent Properties

- **NAME**: `'post.handle'` - The event identifier
- **Target**: The command result (nullable)
- **Params**: Additional parameters (optional)

### PostHandleEvent Usage

#### Listening to PostHandleEvent

```php
use PhpCmd\Event\PostHandleEvent;
use Laminas\EventManager\EventManagerInterface;

$eventManager->attach(
    PostHandleEvent::NAME,
    function (PostHandleEvent $event) {
        $result = $event->getTarget();
        // Your post-handle logic
    },
    100 // Priority
);
```

#### PostHandleEvent Common Use Cases

##### 1. Success Logging

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $result = $event->getTarget();

    $this->logger->info('Command executed successfully', [
        'result' => $result,
        'timestamp' => time(),
    ]);
}
```

##### 2. Event Broadcasting

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $result = $event->getTarget();

    if ($result instanceof CommandResultInterface) {
        $command = $result->getCommand();

        if ($command instanceof CreateUserCommand) {
            $this->eventBroadcaster->broadcast(
                'user.created',
                ['user' => $result->getResult()]
            );
        }
    }
}
```

##### 3. Cache Invalidation

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $result = $event->getTarget();

    if ($result instanceof CommandResultInterface) {
        $command = $result->getCommand();

        if ($command instanceof CacheInvalidatingCommand) {
            foreach ($command->getCacheKeys() as $key) {
                $this->cache->delete($key);
            }
        }
    }
}
```

##### 4. Notifications

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $result = $event->getTarget();

    if ($result instanceof CommandResultInterface) {
        $command = $result->getCommand();

        if ($command instanceof NotifiableCommand) {
            $this->notifier->notify(
                $command->getRecipients(),
                $command->getNotificationMessage(),
                $result->getResult()
            );
        }
    }
}
```

##### 5. Audit Trail

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $result = $event->getTarget();

    if ($result instanceof CommandResultInterface) {
        $command = $result->getCommand();

        $this->auditLog->record([
            'command' => $command::class,
            'user_id' => $this->authService->getUserId(),
            'result' => $result->getResult(),
            'timestamp' => time(),
        ]);
    }
}
```

## Event Properties

### Accessing Event Data

All events extend `Laminas\EventManager\Event`, providing these methods:

```php
// Get event name
$name = $event->getName(); // 'pre.handle' or 'post.handle'

// Get target (command or result)
$target = $event->getTarget();

// Get/Set parameters
$params = $event->getParams();
$event->setParams(['key' => 'value']);
$event->setParam('key', 'value');
$value = $event->getParam('key', $default);
```

### Event Metadata

You can attach metadata to events:

```php
public function onPreHandle(PreHandleEvent $event): void
{
    // Add metadata for downstream listeners
    $event->setParam('request_id', $this->requestId);
    $event->setParam('correlation_id', $this->correlationId);
    $event->setParam('start_time', microtime(true));
}

public function onPostHandle(PostHandleEvent $event): void
{
    // Access metadata from earlier in the pipeline
    $startTime = $event->getParam('start_time');
    $duration = microtime(true) - $startTime;

    $this->metrics->timing('command.duration', $duration);
}
```

## Working with Events

### Stopping Event Propagation

Prevent subsequent listeners from executing:

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    // Check cache
    if ($this->cache->has($command->getCacheKey())) {
        // Stop other listeners
        $event->stopPropagation(true);

        // Set result
        $event->setParam('result', $this->cache->get($command->getCacheKey()));
    }
}
```

### Event Results

Collect results from listeners:

```php
$eventManager->attach(
    PreHandleEvent::NAME,
    function (PreHandleEvent $event) {
        return 'listener1_result';
    }
);

$results = $eventManager->triggerEventUntil(
    new PreHandleEvent($command),
    function ($result) {
        // Stop when we get a truthy result
        return $result !== null;
    }
);

$lastResult = $results->last();
```

### Error Handling in Listeners

```php
public function onPreHandle(PreHandleEvent $event): void
{
    try {
        $this->performValidation($event->getTarget());
    } catch (ValidationException $e) {
        $this->logger->error('Validation failed', [
            'error' => $e->getMessage(),
            'command' => $event->getTarget()::class,
        ]);

        // Re-throw to halt command execution
        throw $e;
    }
}
```

## Event Propagation

### Listener Priority

Listeners execute in priority order (highest first):

```php
$eventManager->attach(PreHandleEvent::NAME, $listener1, 100); // First
$eventManager->attach(PreHandleEvent::NAME, $listener2, 90);  // Second
$eventManager->attach(PreHandleEvent::NAME, $listener3, 80);  // Third
```

### Conditional Execution

Execute listeners conditionally:

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    // Only process certain commands
    if (!$command instanceof LoggableCommand) {
        return;
    }

    $this->logger->info('Processing command', [
        'command' => $command::class,
    ]);
}
```

## Best Practices

1. **Keep listeners focused**: Each listener should have a single responsibility
2. **Use priorities wisely**: Set appropriate priorities for execution order
3. **Handle errors gracefully**: Catch and log exceptions appropriately
4. **Avoid side effects in pre-handle**: Pre-handle should primarily validate/prepare
5. **Document event contracts**: Clearly document what data events carry
6. **Use type hints**: Leverage PHP type system for event parameters
7. **Test listeners independently**: Unit test each listener in isolation

## See Also

- [Listener Configuration](./listeners.md)
- [Middleware Guide](./middleware.md)
- [Best Practices](./best-practices.md)
- [Examples](./examples)
