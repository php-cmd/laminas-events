# Best Practices

This guide provides best practices and recommendations for using the Laminas Events integration with CmdBus effectively.

## Table of Contents

- [Architecture](#architecture)
- [Event Listeners](#event-listeners)
- [Error Handling](#error-handling)
- [Performance](#performance)
- [Testing](#testing)
- [Security](#security)
- [Maintainability](#maintainability)

## Architecture

### Separation of Concerns

Keep your event listeners focused on a single responsibility:

**✅ Good:**

```php
// Focused on validation only
final class ValidationListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        $this->validateCommand($event->getTarget());
    }
}

// Focused on logging only
final class LoggingListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        $this->logCommand($event->getTarget());
    }
}
```

**❌ Bad:**

```php
// Mixed responsibilities
final class AllInOneListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        $this->validateCommand($event->getTarget());
        $this->logCommand($event->getTarget());
        $this->sendNotification($event->getTarget());
        $this->updateCache($event->getTarget());
    }
}
```

### Command Design

Design commands to be immutable and self-contained:

**✅ Good:**

```php
final class CreateUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly array $roles
    ) {}
}
```

**❌ Bad:**

```php
final class CreateUserCommand
{
    public string $email;
    public string $name;
    public array $roles;

    // Mutable state leads to unpredictable behavior
}
```

### Event-Driven vs Direct Coupling

Use events for cross-cutting concerns, not core business logic:

**✅ Good:**

```php
// Core business logic in handler
final class CreateUserHandler
{
    public function handle(CommandInterface $command): User
    {
        // Core logic here
        return $this->userRepository->create($command);
    }
}

// Cross-cutting concern in listener
final class UserCreatedNotificationListener
{
    public function onPostHandle(PostHandleEvent $event): void
    {
        // Send notification
    }
}
```

**❌ Bad:**

```php
// Don't put core business logic in listeners
final class UserCreationListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        // Creating user in listener - wrong place!
        $this->userRepository->create($event->getTarget());
    }
}
```

## Event Listeners

### Listener Priority Strategy

Establish a consistent priority strategy:

```php
// config/autoload/listeners.global.php
return [
    'listeners' => [
        // 1000+ Critical pre-processing (auth, rate limiting)
        [
            'listener' => AuthenticationListener::class,
            'priority' => 1000,
        ],

        // 500-999 Security checks
        [
            'listener' => AuthorizationListener::class,
            'priority' => 900,
        ],

        // 100-499 Validation and preparation
        [
            'listener' => ValidationListener::class,
            'priority' => 400,
        ],

        // 1-99 Standard processing (logging, metrics)
        [
            'listener' => LoggingListener::class,
            'priority' => 50,
        ],

        // 0 to -99 Post-processing
        [
            'listener' => CacheListener::class,
            'priority' => -50,
        ],

        // -100 to -999 Notifications and events
        [
            'listener' => NotificationListener::class,
            'priority' => -100,
        ],
    ],
];
```

### Idempotent Listeners

Design listeners to be idempotent when possible:

**✅ Good:**

```php
final class CacheInvalidationListener
{
    public function onPostHandle(PostHandleEvent $event): void
    {
        $keys = $this->getCacheKeys($event);

        foreach ($keys as $key) {
            // Safe to call multiple times
            $this->cache->delete($key);
        }
    }
}
```

**❌ Bad:**

```php
final class CounterListener
{
    public function onPostHandle(PostHandleEvent $event): void
    {
        // Not idempotent - will increment multiple times if called again
        $this->counter->increment();
    }
}
```

### Conditional Execution

Use guard clauses for conditional logic:

**✅ Good:**

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    // Early return if not applicable
    if (!$command instanceof AuditableCommand) {
        return;
    }

    if (!$this->shouldAudit($command)) {
        return;
    }

    $this->auditLog->record($command);
}
```

**❌ Bad:**

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    if ($command instanceof AuditableCommand) {
        if ($this->shouldAudit($command)) {
            $this->auditLog->record($command);
        }
    }
}
```

## Error Handling

### Fail Fast on Critical Errors

Throw exceptions for critical failures in pre-handle listeners:

```php
final class AuthorizationListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        $command = $event->getTarget();

        if (!$this->authService->isAuthorized($command)) {
            // Fail fast - don't continue
            throw new UnauthorizedException(
                'User not authorized to execute this command'
            );
        }
    }
}
```

### Graceful Degradation in Post-Handle

Handle errors gracefully in post-handle listeners:

```php
final class NotificationListener
{
    public function onPostHandle(PostHandleEvent $event): void
    {
        try {
            $this->sendNotification($event);
        } catch (\Throwable $e) {
            // Log but don't fail the command
            $this->logger->error('Notification failed', [
                'error' => $e->getMessage(),
            ]);

            // Optionally queue for retry
            $this->queue->push(new RetryNotification($event));
        }
    }
}
```

### Error Context

Provide meaningful error context:

**✅ Good:**

```php
throw new ValidationException(
    'Command validation failed',
    [
        'command' => $command::class,
        'errors' => $this->validator->getErrors(),
        'user_id' => $this->authService->getUserId(),
        'timestamp' => time(),
    ]
);
```

**❌ Bad:**

```php
throw new \Exception('Validation failed');
```

## Performance

### Lazy Loading

Use lazy loading for expensive dependencies:

```php
final class MetricsListener
{
    private ?MetricsCollector $metrics = null;

    public function __construct(
        private readonly ContainerInterface $container
    ) {}

    public function onPostHandle(PostHandleEvent $event): void
    {
        // Only load when needed
        $this->getMetrics()->record($event);
    }

    private function getMetrics(): MetricsCollector
    {
        if ($this->metrics === null) {
            $this->metrics = $this->container->get(MetricsCollector::class);
        }

        return $this->metrics;
    }
}
```

### Avoid N+1 Queries

Batch operations when possible:

**✅ Good:**

```php
final class BatchNotificationListener
{
    private array $pendingNotifications = [];

    public function onPostHandle(PostHandleEvent $event): void
    {
        $this->pendingNotifications[] = $this->prepareNotification($event);
    }

    public function __destruct()
    {
        if (!empty($this->pendingNotifications)) {
            // Send all at once
            $this->notifier->sendBatch($this->pendingNotifications);
        }
    }
}
```

**❌ Bad:**

```php
final class ImmediateNotificationListener
{
    public function onPostHandle(PostHandleEvent $event): void
    {
        // Sends one notification per command
        $this->notifier->send($this->prepareNotification($event));
    }
}
```

### Asynchronous Processing

Use queues for time-consuming tasks:

```php
final class AsyncEmailListener
{
    public function onPostHandle(PostHandleEvent $event): void
    {
        // Don't send email immediately
        // Queue it for background processing
        $this->queue->push(new SendEmailJob(
            $this->prepareEmailData($event)
        ));
    }
}
```

### Caching

Cache expensive computations:

```php
final class CachingValidationListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        $command = $event->getTarget();
        $cacheKey = $this->getCacheKey($command);

        // Check cache first
        if ($this->cache->has($cacheKey)) {
            $isValid = $this->cache->get($cacheKey);

            if (!$isValid) {
                throw new ValidationException('Cached validation failed');
            }

            return;
        }

        // Validate and cache result
        $isValid = $this->validator->validate($command);
        $this->cache->set($cacheKey, $isValid, 300);

        if (!$isValid) {
            throw new ValidationException('Validation failed');
        }
    }
}
```

## Testing

### Unit Test Listeners Independently

```php
final class ValidationListenerTest extends TestCase
{
    private ValidationListener $listener;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->listener = new ValidationListener($this->validator);
    }

    public function testValidCommandPasses(): void
    {
        $command = new TestCommand();
        $event = new PreHandleEvent($command);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($command)
            ->willReturn(true);

        // Should not throw
        $this->listener->onPreHandle($event);
        $this->assertTrue(true);
    }

    public function testInvalidCommandThrows(): void
    {
        $this->expectException(ValidationException::class);

        $command = new TestCommand();
        $event = new PreHandleEvent($command);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($command)
            ->willReturn(false);

        $this->listener->onPreHandle($event);
    }
}
```

### Integration Tests

Test the full event flow:

```php
final class EventIntegrationTest extends TestCase
{
    private ContainerInterface $container;
    private CmdBusInterface $cmdBus;

    protected function setUp(): void
    {
        $this->container = $this->createContainer();
        $this->cmdBus = $this->container->get(CmdBusInterface::class);
    }

    public function testEventsAreFired(): void
    {
        $listener = $this->container->get(TestListener::class);

        $this->cmdBus->handle(new TestCommand());

        $this->assertTrue($listener->preHandleWasCalled());
        $this->assertTrue($listener->postHandleWasCalled());
    }
}
```

### Test Doubles

Use test doubles for dependencies:

```php
final class LoggingListenerTest extends TestCase
{
    public function testLogsCommand(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $listener = new LoggingListener($logger);

        $command = new TestCommand();
        $event = new PreHandleEvent($command);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Command received', [
                'command' => TestCommand::class,
            ]);

        $listener->onPreHandle($event);
    }
}
```

## Security

### Authentication First

Always check authentication before other validations:

```php
return [
    'listeners' => [
        [
            'listener' => AuthenticationListener::class,
            'priority' => 1000, // Highest priority
        ],
        [
            'listener' => AuthorizationListener::class,
            'priority' => 900,
        ],
        [
            'listener' => ValidationListener::class,
            'priority' => 800,
        ],
    ],
];
```

### Sanitize Logs

Don't log sensitive information:

**✅ Good:**

```php
$this->logger->info('User command executed', [
    'command' => $command::class,
    'user_id' => $command->getUserId(),
]);
```

**❌ Bad:**

```php
$this->logger->info('User command executed', [
    'command' => $command::class,
    'password' => $command->getPassword(), // Never log passwords!
    'credit_card' => $command->getCreditCard(), // Or sensitive data!
]);
```

### Validate Input

Always validate command data before execution:

```php
final class InputValidationListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        $command = $event->getTarget();

        // Validate all input
        $violations = $this->validator->validate($command);

        if (count($violations) > 0) {
            throw new ValidationException(
                'Invalid command data',
                $violations
            );
        }
    }
}
```

## Maintainability

### Document Configuration

Document your listener configuration:

```php
// config/autoload/listeners.global.php

/**
 * Event Listeners Configuration
 *
 * Listeners are executed in priority order (highest first).
 *
 * Priority ranges:
 * - 1000+: Authentication and rate limiting
 * - 500-999: Authorization and security
 * - 100-499: Validation and preparation
 * - 1-99: Logging and metrics
 * - 0 to -99: Cache and cleanup
 * - -100+: Notifications and events
 */
return [
    'listeners' => [
        // ...
    ],
];
```

### Use Descriptive Names

**✅ Good:**

```php
final class UserCommandAuthorizationListener { }
final class CreateUserAuditLogger { }
final class CacheInvalidationListener { }
```

**❌ Bad:**

```php
final class Listener1 { }
final class Handler { }
final class Helper { }
```

### Keep Configuration DRY

Extract common patterns:

```php
// config/listeners.php

final class ListenerConfigBuilder
{
    public static function buildAuditListeners(): array
    {
        return [
            [
                'listener' => CommandAuditListener::class,
                'priority' => 100,
            ],
            [
                'listener' => ResultAuditListener::class,
                'priority' => -100,
            ],
        ];
    }
}

// config/autoload/listeners.global.php
return [
    'listeners' => array_merge(
        ListenerConfigBuilder::buildAuditListeners(),
        // Other listeners...
    ),
];
```

### Version Control Configuration

Keep environment-specific config out of version control:

```php
// config/autoload/listeners.global.php (in VCS)
return [
    'listeners' => [
        // Common listeners
    ],
];

// config/autoload/listeners.local.php (not in VCS)
return [
    'listeners' => [
        // Environment-specific listeners
        [
            'listener' => DebugListener::class,
            'priority' => 1000,
        ],
    ],
];
```

## Summary

1. **Keep listeners focused** on single responsibilities
2. **Use appropriate priorities** for execution order
3. **Handle errors gracefully**, fail fast when needed
4. **Optimize for performance** with lazy loading and caching
5. **Test thoroughly** with unit and integration tests
6. **Prioritize security** with authentication and validation
7. **Maintain readability** with good naming and documentation

## See Also

- [Getting Started](./getting-started.md)
- [Configuration Guide](./configuration.md)
- [Events Reference](./events.md)
- [Listener Configuration](./listeners.md)
- [Examples](./examples)
