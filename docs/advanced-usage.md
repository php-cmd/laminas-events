# Advanced Usage

This guide covers advanced patterns and techniques for using the Laminas Events integration with CmdBus.

## Table of Contents

- [Dynamic Event Registration](#dynamic-event-registration)
- [Event Propagation Control](#event-propagation-control)
- [Shared Event Managers](#shared-event-managers)
- [Event Filtering](#event-filtering)
- [Custom Events](#custom-events)
- [Event Sourcing](#event-sourcing)
- [CQRS Patterns](#cqrs-patterns)

## Dynamic Event Registration

### Runtime Listener Registration

Register listeners dynamically at runtime:

```php
<?php

use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\PreHandleEvent;

final class DynamicListenerManager
{
    public function __construct(
        private readonly EventManagerInterface $eventManager
    ) {}

    public function registerConditionalListeners(array $config): void
    {
        foreach ($config['features'] ?? [] as $feature => $enabled) {
            if ($enabled) {
                $this->registerFeatureListeners($feature);
            }
        }
    }

    private function registerFeatureListeners(string $feature): void
    {
        match ($feature) {
            'audit' => $this->registerAuditListener(),
            'metrics' => $this->registerMetricsListener(),
            'notifications' => $this->registerNotificationListener(),
            default => null,
        };
    }

    private function registerAuditListener(): void
    {
        $this->eventManager->attach(
            PreHandleEvent::NAME,
            function (PreHandleEvent $event) {
                // Audit logic
            },
            100
        );
    }
}
```

### Feature Flags

Use feature flags to control listener activation:

```php
<?php

final class FeatureFlagListener
{
    public function __construct(
        private readonly FeatureFlagService $featureFlags
    ) {}

    public function onPreHandle(PreHandleEvent $event): void
    {
        if (!$this->featureFlags->isEnabled('command_logging')) {
            return;
        }

        // Only execute if feature is enabled
        $this->logCommand($event->getTarget());
    }
}
```

### Plugin-Based Listeners

Create a plugin system for listeners:

```php
<?php

interface ListenerPluginInterface
{
    public function register(EventManagerInterface $eventManager): void;
    public function getPluginName(): string;
}

final class ListenerPluginManager
{
    private array $plugins = [];

    public function registerPlugin(ListenerPluginInterface $plugin): void
    {
        $this->plugins[$plugin->getPluginName()] = $plugin;
    }

    public function activatePlugins(EventManagerInterface $eventManager): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin->register($eventManager);
        }
    }
}

final class AuditPlugin implements ListenerPluginInterface
{
    public function register(EventManagerInterface $eventManager): void
    {
        $eventManager->attach(PreHandleEvent::NAME, [$this, 'audit'], 100);
    }

    public function getPluginName(): string
    {
        return 'audit';
    }

    public function audit(PreHandleEvent $event): void
    {
        // Audit logic
    }
}
```

## Event Propagation Control

### Stopping Propagation

Control event flow by stopping propagation:

```php
<?php

final class CachingListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        $command = $event->getTarget();

        if (!$command instanceof CacheableCommand) {
            return;
        }

        $cacheKey = $command->getCacheKey();

        if ($this->cache->has($cacheKey)) {
            // Stop propagation - don't execute subsequent listeners
            $event->stopPropagation(true);

            // Store cached result for retrieval
            $event->setParam('cached_result', $this->cache->get($cacheKey));

            // Mark as cached
            $event->setParam('from_cache', true);
        }
    }
}
```

### Conditional Propagation

Stop propagation based on conditions:

```php
<?php

final class RateLimitListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        $command = $event->getTarget();
        $userId = $this->authService->getCurrentUserId();

        if ($this->rateLimiter->isLimitExceeded($userId, $command::class)) {
            // Stop propagation and throw exception
            $event->stopPropagation(true);

            throw new RateLimitExceededException(
                'Rate limit exceeded for this command'
            );
        }
    }
}
```

### Result Collection

Collect results from multiple listeners:

```php
<?php

use Laminas\EventManager\ResponseCollection;

final class ValidationCoordinator
{
    public function validateCommand(CommandInterface $command): bool
    {
        $event = new PreHandleEvent($command);

        /** @var ResponseCollection $results */
        $results = $this->eventManager->triggerEventUntil(
            $event,
            function ($result) {
                // Stop on first false result
                return $result === false;
            }
        );

        // Check if any validator returned false
        return !$results->stopped();
    }
}
```

## Shared Event Managers

### Cross-Context Events

Share events across different contexts:

```php
<?php

use Laminas\EventManager\SharedEventManagerInterface;

final class CrossContextEventManager
{
    public function __construct(
        private readonly SharedEventManagerInterface $sharedEvents
    ) {}

    public function setupSharedListeners(): void
    {
        // Listen to events from any CommandBus instance
        $this->sharedEvents->attach(
            'CommandBus',
            PreHandleEvent::NAME,
            function (PreHandleEvent $event) {
                // This will be triggered for all CommandBus instances
                $this->logGlobally($event->getTarget());
            },
            100
        );
    }
}
```

### Event Identifiers

Use event identifiers for targeted listening:

```php
<?php

final class IdentifiedEventManager
{
    public function setupIdentifiers(EventManagerInterface $eventManager): void
    {
        $eventManager->setIdentifiers([
            'MyApplication',
            'CommandBus',
            'UserModule',
        ]);
    }

    public function attachToIdentifier(SharedEventManagerInterface $sharedEvents): void
    {
        // Only listen to events from UserModule
        $sharedEvents->attach(
            'UserModule',
            PreHandleEvent::NAME,
            function (PreHandleEvent $event) {
                // Handle user module events
            }
        );
    }
}
```

### Module-Specific Events

Organize events by module:

```php
<?php

final class ModuleEventOrganizer
{
    public function setupModuleEvents(
        SharedEventManagerInterface $sharedEvents
    ): void {
        // User module events
        $sharedEvents->attach(
            'App\\Module\\User',
            '*',
            [$this, 'handleUserEvents']
        );

        // Product module events
        $sharedEvents->attach(
            'App\\Module\\Product',
            '*',
            [$this, 'handleProductEvents']
        );
    }

    public function handleUserEvents($event): void
    {
        // User-specific event handling
    }

    public function handleProductEvents($event): void
    {
        // Product-specific event handling
    }
}
```

## Event Filtering

### Command Type Filtering

Filter events by command type:

```php
<?php

final class TypeFilteringListener
{
    private array $allowedTypes = [
        CreateUserCommand::class,
        UpdateUserCommand::class,
    ];

    public function onPreHandle(PreHandleEvent $event): void
    {
        $command = $event->getTarget();

        if (!$this->isAllowedType($command)) {
            return;
        }

        $this->processCommand($command);
    }

    private function isAllowedType(CommandInterface $command): bool
    {
        foreach ($this->allowedTypes as $type) {
            if ($command instanceof $type) {
                return true;
            }
        }

        return false;
    }
}
```

### Attribute-Based Filtering

Use PHP attributes for filtering:

```php
<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Auditable
{
    public function __construct(
        public readonly string $category = 'general'
    ) {}
}

#[Auditable(category: 'user')]
final class CreateUserCommand implements CommandInterface
{
    // ...
}

final class AttributeBasedAuditListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        $command = $event->getTarget();
        $reflection = new \ReflectionClass($command);

        $attributes = $reflection->getAttributes(Auditable::class);

        if (empty($attributes)) {
            return;
        }

        /** @var Auditable $auditable */
        $auditable = $attributes[0]->newInstance();

        $this->audit($command, $auditable->category);
    }
}
```

### Context-Based Filtering

Filter based on execution context:

```php
<?php

final class ContextAwareListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        $context = $event->getParam('context');

        match ($context) {
            'cli' => $this->handleCliContext($event),
            'web' => $this->handleWebContext($event),
            'api' => $this->handleApiContext($event),
            default => null,
        };
    }
}
```

## Custom Events

### Creating Custom Events

Extend the event system with custom events:

```php
<?php

namespace App\Event;

use Laminas\EventManager\Event;
use PhpCmd\CmdBus\CommandInterface;

final class ValidationEvent extends Event
{
    public const NAME = 'command.validation';

    public function __construct(
        CommandInterface $command,
        array $violations
    ) {
        parent::__construct(self::NAME, $command, [
            'violations' => $violations,
        ]);
    }

    public function getCommand(): CommandInterface
    {
        return $this->getTarget();
    }

    public function getViolations(): array
    {
        return $this->getParam('violations', []);
    }
}
```

### Triggering Custom Events

```php
<?php

final class CustomEventMiddleware implements MiddlewareInterface
{
    use EventManagerAwareTrait;

    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        // Validate command
        $violations = $this->validator->validate($command);

        if (!empty($violations)) {
            // Trigger custom event
            $this->getEventManager()->triggerEvent(
                new ValidationEvent($command, $violations)
            );

            throw new ValidationException('Validation failed', $violations);
        }

        return $handler->handle($command);
    }
}
```

### Custom Event Listeners

```php
<?php

final class ValidationEventListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function onValidationFailed(ValidationEvent $event): void
    {
        $this->logger->warning('Command validation failed', [
            'command' => $event->getCommand()::class,
            'violations' => $event->getViolations(),
        ]);
    }
}
```

## Event Sourcing

### Event Store Integration

Integrate with event sourcing:

```php
<?php

final class EventSourcingListener
{
    public function __construct(
        private readonly EventStoreInterface $eventStore
    ) {}

    public function onPostHandle(PostHandleEvent $event): void
    {
        $result = $event->getTarget();

        if (!$result instanceof CommandResultInterface) {
            return;
        }

        $command = $result->getCommand();

        if (!$command instanceof EventSourcedCommand) {
            return;
        }

        // Store domain events
        $domainEvents = $command->releaseEvents();

        foreach ($domainEvents as $domainEvent) {
            $this->eventStore->append($domainEvent);
        }
    }
}
```

### Event Replay

Implement event replay functionality:

```php
<?php

final class EventReplayService
{
    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly CmdBusInterface $cmdBus
    ) {}

    public function replay(string $aggregateId): void
    {
        $events = $this->eventStore->getEventsForAggregate($aggregateId);

        foreach ($events as $event) {
            $command = $this->reconstructCommand($event);
            $this->cmdBus->handle($command);
        }
    }

    private function reconstructCommand(DomainEvent $event): CommandInterface
    {
        // Reconstruct command from event
        return new ReplayCommand($event);
    }
}
```

## CQRS Patterns

### Command-Query Separation

Implement CQRS patterns:

```php
<?php

final class CQRSEventListener
{
    public function __construct(
        private readonly ReadModelUpdater $readModelUpdater
    ) {}

    public function onPostHandle(PostHandleEvent $event): void
    {
        $result = $event->getTarget();

        if (!$result instanceof CommandResultInterface) {
            return;
        }

        $command = $result->getCommand();

        // Update read models after command execution
        if ($command instanceof ModifiesReadModel) {
            $this->readModelUpdater->update(
                $command->getReadModelName(),
                $result->getResult()
            );
        }
    }
}
```

### Projection Updates

Update projections based on events:

```php
<?php

final class ProjectionListener
{
    public function __construct(
        private readonly ProjectionManager $projectionManager
    ) {}

    public function onPostHandle(PostHandleEvent $event): void
    {
        $result = $event->getTarget();

        if (!$result instanceof CommandResultInterface) {
            return;
        }

        $projections = $this->determineAffectedProjections($result);

        foreach ($projections as $projection) {
            $this->projectionManager->rebuild($projection);
        }
    }

    private function determineAffectedProjections(
        CommandResultInterface $result
    ): array {
        // Determine which projections need updating
        return [];
    }
}
```

## Advanced Patterns

### Saga Pattern

Implement saga orchestration:

```php
<?php

final class SagaOrchestrator
{
    private array $sagas = [];

    public function registerSaga(SagaInterface $saga): void
    {
        $this->sagas[] = $saga;
    }

    public function onPostHandle(PostHandleEvent $event): void
    {
        $result = $event->getTarget();

        foreach ($this->sagas as $saga) {
            if ($saga->canHandle($result)) {
                $saga->handle($result);
            }
        }
    }
}

interface SagaInterface
{
    public function canHandle(CommandResultInterface $result): bool;
    public function handle(CommandResultInterface $result): void;
}
```

### Event Aggregation

Aggregate multiple events:

```php
<?php

final class EventAggregator
{
    private array $events = [];

    public function onPreHandle(PreHandleEvent $event): void
    {
        $this->events[] = [
            'type' => 'pre',
            'command' => $event->getTarget()::class,
            'timestamp' => microtime(true),
        ];
    }

    public function onPostHandle(PostHandleEvent $event): void
    {
        $this->events[] = [
            'type' => 'post',
            'timestamp' => microtime(true),
        ];

        // Analyze aggregated events
        $this->analyzeEvents();
    }

    private function analyzeEvents(): void
    {
        // Perform analysis on aggregated events
        $duration = end($this->events)['timestamp'] - $this->events[0]['timestamp'];

        // Do something with the data
    }
}
```

## See Also

- [Events Reference](./events.md)
- [Middleware Guide](./middleware.md)
- [Best Practices](./best-practices.md)
- [Examples](./examples)
