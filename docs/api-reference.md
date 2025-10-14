# API Reference

<!-- markdownlint-disable MD024 -->
<!-- Duplicate headings are necessary for API documentation of multiple classes with similar methods -->

Complete API documentation for the Laminas Events integration for CmdBus.

## Table of Contents

- [Events](#events)
- [Middleware](#middleware)
- [Containers](#containers)
- [Exceptions](#exceptions)
- [Configuration](#configuration)

## Events

### PreHandleEvent

Event triggered before command execution.

**Namespace:** `PhpCmd\Event`

**Extends:** `Laminas\EventManager\Event`

#### Constants

```php
public const NAME = 'pre.handle';
```

Event identifier for pre-handle events.

#### Constructor

```php
public function __construct(
    CommandInterface $target,
    ?array $params = []
): void
```

**Parameters:**

- `$target` - The command being executed
- `$params` - Optional array of additional parameters

**Example:**

```php
$event = new PreHandleEvent($command, ['context' => 'web']);
```

#### Methods

##### getTarget()

```php
public function getTarget(): CommandInterface|NamedCommandInterface
```

Returns the command that will be executed.

**Returns:** `CommandInterface|NamedCommandInterface`

**Example:**

```php
$command = $event->getTarget();
echo $command::class; // App\Command\CreateUserCommand
```

##### getName()

```php
public function getName(): string
```

Returns the event name.

**Returns:** `string` - Always returns `'pre.handle'`

**Inherited from:** `Laminas\EventManager\Event`

##### getParams()

```php
public function getParams(): array
```

Returns all event parameters.

**Returns:** `array`

**Inherited from:** `Laminas\EventManager\Event`

##### getParam()

```php
public function getParam(string $name, mixed $default = null): mixed
```

Returns a specific parameter value.

**Parameters:**

- `$name` - Parameter name
- `$default` - Default value if parameter doesn't exist

**Returns:** `mixed`

**Inherited from:** `Laminas\EventManager\Event`

##### setParam()

```php
public function setParam(string $name, mixed $value): self
```

Sets a parameter value.

**Parameters:**

- `$name` - Parameter name
- `$value` - Parameter value

**Returns:** `self`

**Inherited from:** `Laminas\EventManager\Event`

##### stopPropagation()

```php
public function stopPropagation(bool $flag = true): void
```

Stops event propagation to subsequent listeners.

**Parameters:**

- `$flag` - Whether to stop propagation

**Inherited from:** `Laminas\EventManager\Event`

### PostHandleEvent

Event triggered after command execution.

**Namespace:** `PhpCmd\Event`

**Extends:** `Laminas\EventManager\Event`

#### Constants

```php
public const NAME = 'post.handle';
```

Event identifier for post-handle events.

#### Constructor

```php
public function __construct(
    ?CommandResultInterface $target = null,
    ?array $params = []
): void
```

**Parameters:**

- `$target` - The command result (nullable)
- `$params` - Optional array of additional parameters

**Example:**

```php
$event = new PostHandleEvent($result, ['duration' => 0.15]);
```

#### Methods

Inherits all methods from `Laminas\EventManager\Event`. See [PreHandleEvent methods](#methods) for details.

## Middleware

### PreHandleMiddleware

Middleware that triggers PreHandleEvent before command execution.

**Namespace:** `PhpCmd\Event\Middleware`

**Implements:**

- `PhpCmd\CmdBus\MiddlewareInterface`
- `Laminas\EventManager\EventManagerAwareInterface`

**Uses:** `Laminas\EventManager\EventManagerAwareTrait`

#### Methods

##### process()

```php
public function process(
    CommandInterface $command,
    CommandHandlerInterface $handler
): mixed
```

Processes the command through the middleware.

**Parameters:**

- `$command` - The command to process
- `$handler` - The next handler in the chain

**Returns:** `mixed` - Result from the command handler

**Throws:** Any exception thrown by event listeners or the handler

**Example:**

```php
$result = $middleware->process($command, $handler);
```

##### setEventManager()

```php
public function setEventManager(EventManagerInterface $eventManager): void
```

Sets the event manager.

**Parameters:**

- `$eventManager` - Event manager instance

**From:** `EventManagerAwareTrait`

##### getEventManager()

```php
public function getEventManager(): EventManagerInterface
```

Gets the event manager.

**Returns:** `EventManagerInterface`

**From:** `EventManagerAwareTrait`

### PostHandleMiddleware

Middleware that triggers PostHandleEvent after command execution.

**Namespace:** `PhpCmd\Event\Middleware`

**Implements:**

- `PhpCmd\CmdBus\MiddlewareInterface`
- `Laminas\EventManager\EventManagerAwareInterface`

**Uses:** `Laminas\EventManager\EventManagerAwareTrait`

#### Methods

##### process()

```php
public function process(
    CommandInterface $command,
    CommandHandlerInterface $handler
): mixed
```

Processes the command through the middleware.

**Parameters:**

- `$command` - The command to process
- `$handler` - The next handler in the chain

**Returns:** `mixed` - Unwrapped result or raw handler result

**Note:** If handler returns `CommandResult`, the middleware triggers `PostHandleEvent` and returns the unwrapped result via `getResult()`.

**Example:**

```php
$result = $middleware->process($command, $handler);
```

##### setEventManager()

```php
public function setEventManager(EventManagerInterface $eventManager): void
```

Sets the event manager.

**Parameters:**

- `$eventManager` - Event manager instance

**From:** `EventManagerAwareTrait`

##### getEventManager()

```php
public function getEventManager(): EventManagerInterface
```

Gets the event manager.

**Returns:** `EventManagerInterface`

**From:** `EventManagerAwareTrait`

## Containers

### EventManagerFactory

Factory for creating EventManager instances.

**Namespace:** `PhpCmd\Event\Container`

#### Methods

##### __invoke()

```php
public function __invoke(ContainerInterface $container): EventManager
```

Creates and returns an EventManager instance.

**Parameters:**

- `$container` - PSR-11 container

**Returns:** `EventManager` - Configured with SharedEventManager

**Throws:**

- `ContainerExceptionInterface` if SharedEventManager not found
- `AssertionError` if service is not SharedEventManagerInterface

**Example:**

```php
$factory = new EventManagerFactory();
$eventManager = $factory($container);
```

### EventManagerAwareDelegator

Delegator that injects EventManager into event-aware services.

**Namespace:** `PhpCmd\Event\Container`

#### Methods

##### __invoke()

```php
public function __invoke(
    ContainerInterface $container,
    string $serviceName,
    callable $callback
): EventManagerAwareInterface
```

Decorates a service with EventManager injection.

**Parameters:**

- `$container` - PSR-11 container
- `$serviceName` - Name of the service being created
- `$callback` - Callback that returns the service instance

**Returns:** `EventManagerAwareInterface` - Service with EventManager injected

**Throws:** `AssertionError` if service is not EventManagerAwareInterface

**Example:**

```php
$delegator = new EventManagerAwareDelegator();
$service = $delegator($container, $serviceName, $callback);
```

### ListenerConfigurationDelegator

Delegator that attaches listeners from configuration to EventManager.

**Namespace:** `PhpCmd\Event\Container`

#### Constants

```php
private const DEFAULT_PRIORITY = 1;
```

Default priority for listeners when not specified.

#### Methods

##### __invoke()

```php
public function __invoke(
    ContainerInterface $container,
    string $serviceName,
    callable $callback
): EventManagerInterface
```

Decorates EventManager with configured listeners.

**Parameters:**

- `$container` - PSR-11 container
- `$serviceName` - Name of the service being created
- `$callback` - Callback that returns the EventManager instance

**Returns:** `EventManagerInterface` - EventManager with listeners attached

**Throws:**

- `InvalidServiceException` if service is not EventManager
- `ContainerExceptionInterface` if listener services not found

**Configuration Format:**

```php
[
    'listeners' => [
        [
            'listener' => MyListener::class,  // Required
            'priority' => 100,                // Optional (default: 1)
            'event' => 'event.name',          // Optional for callables
            // or
            'event' => ['event1', 'event2'],  // Multiple events
        ],
    ],
]
```

**Example:**

```php
$delegator = new ListenerConfigurationDelegator();
$eventManager = $delegator($container, $serviceName, $callback);
```

## Exceptions

### InvalidServiceException

Exception thrown when an invalid service is provided.

**Namespace:** `PhpCmd\Event\Exception`

**Extends:** `InvalidArgumentException`

**Implements:** `ContainerExceptionInterface`

#### Usage

Thrown by `ListenerConfigurationDelegator` when the decorated service is not an `EventManager` instance.

**Example:**

```php
throw new InvalidServiceException(sprintf(
    'Delegator factory %s cannot operate on a %s',
    self::class,
    $eventManager::class
));
```

## Configuration

### ConfigProvider

Main configuration provider for the package.

**Namespace:** `PhpCmd\Event`

#### Methods

##### __invoke()

```php
public function __invoke(): array
```

Returns the complete configuration array.

**Returns:** `array` with keys:

- `dependencies` - Service manager configuration
- `PhpCmd\CmdBus\ConfigProvider` - CmdBus-specific configuration

**Example:**

```php
$provider = new ConfigProvider();
$config = $provider();
```

##### getDependencies()

```php
public function getDependencies(): array
```

Returns service manager dependencies configuration.

**Returns:** `array` with keys:

- `aliases` - Service aliases
- `delegators` - Delegator factories
- `factories` - Service factories
- `invokables` - Invokable services

**Example:**

```php
$provider = new ConfigProvider();
$dependencies = $provider->getDependencies();
```

##### getMiddleware()

```php
public function getMiddleware(): array
```

Returns middleware pipeline configuration.

**Returns:** `array` - Middleware specifications with priorities

**Example:**

```php
$provider = new ConfigProvider();
$middleware = $provider->getMiddleware();
```

##### getCommandMap()

```php
public function getCommandMap(): array
```

Returns command-to-handler mapping.

**Returns:** `array` - Empty array by default (override in your config)

**Example:**

```php
$provider = new ConfigProvider();
$commandMap = $provider->getCommandMap();
```

## Type Definitions

### ServiceManagerConfiguration

PHPStan type for service manager configuration:

```php
/**
 * @phpstan-type ServiceManagerConfiguration array{
 *     aliases?: array<string, string>,
 *     delegators?: array<string, list<class-string>>,
 *     factories?: array<string, class-string>,
 *     invokables?: array<string, class-string>,
 *     services?: array<string, object>
 * }
 */
```

### MiddlewarePipeSpec

PHPStan type for middleware pipeline specification:

```php
/**
 * @phpstan-type MiddlewarePipeSpec array<string, array{
 *     middleware: class-string,
 *     priority: int
 * }>
 */
```

### CommandMap

PHPStan type for command-to-handler mapping:

```php
/**
 * @phpstan-type CommandMap array<class-string<CommandInterface>, class-string>
 */
```

## Usage Examples

### Creating and Triggering Events

```php
use PhpCmd\Event\PreHandleEvent;
use Laminas\EventManager\EventManagerInterface;

$eventManager = $container->get(EventManagerInterface::class);
$command = new MyCommand();

// Create and trigger event
$event = new PreHandleEvent($command, ['context' => 'web']);
$eventManager->triggerEvent($event);
```

### Implementing Custom Middleware

```php
use PhpCmd\CmdBus\MiddlewareInterface;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;

final class CustomMiddleware implements
    MiddlewareInterface,
    EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        // Use event manager
        $this->getEventManager()->trigger('custom.event', $command);

        return $handler->handle($command);
    }
}
```

### Registering Services

```php
return [
    'dependencies' => [
        'factories' => [
            MyListener::class => MyListenerFactory::class,
        ],
    ],
    'listeners' => [
        [
            'listener' => MyListener::class,
            'priority' => 100,
        ],
    ],
];
```

## See Also

- [Getting Started](./getting-started.md)
- [Events Reference](./events.md)
- [Middleware Guide](./middleware.md)
- [Configuration Guide](./configuration.md)
