# Troubleshooting

Common issues and their solutions when using the Laminas Events integration for CmdBus.

## Table of Contents

- [Installation Issues](#installation-issues)
- [Configuration Problems](#configuration-problems)
- [Event Listener Issues](#event-listener-issues)
- [Middleware Problems](#middleware-problems)
- [Performance Issues](#performance-issues)
- [Debugging Tips](#debugging-tips)

## Installation Issues

### Composer Installation Fails

**Problem:** Composer cannot install the package.

**Symptoms:**

```bash
Problem 1
  - php-cmd/laminas-events requires php ^8.2
```

**Solution:**

Ensure you have PHP 8.2 or higher:

```bash
php --version
```

If your PHP version is too old, upgrade PHP or adjust your `composer.json`:

```json
{
    "config": {
        "platform": {
            "php": "8.2.0"
        }
    }
}
```

### Missing Dependencies

**Problem:** Class not found errors after installation.

**Symptoms:**

```text
Fatal error: Class 'PhpCmd\Event\ConfigProvider' not found
```

**Solution:**

Ensure autoloader is regenerated:

```bash
composer dump-autoload
```

Verify the package is installed:

```bash
composer show php-cmd/laminas-events
```

Check that your autoload configuration includes the vendor directory:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

## Configuration Problems

### ConfigProvider Not Loaded

**Problem:** Services are not registered.

**Symptoms:**

```text
Service 'Laminas\EventManager\EventManagerInterface' not found
```

**Solution:**

**For Mezzio applications**, ensure ConfigProvider is registered:

```php
// config/config.php
$aggregator = new ConfigAggregator([
    \PhpCmd\Event\ConfigProvider::class,
    // ... other providers
]);
```

**For Laminas MVC**, add to modules:

```php
// config/modules.config.php
return [
    'PhpCmd\\Event',
    // ... other modules
];
```

**For standalone apps**, merge configuration manually:

```php
$config = array_merge_recursive(
    (new \PhpCmd\Event\ConfigProvider())(),
    $yourConfig
);
```

### Listeners Not Firing

**Problem:** Event listeners are not being called.

**Symptoms:**

```php
// No output from listener
public function onPreHandle(PreHandleEvent $event): void
{
    echo "This never prints!";
}
```

**Solutions:**
**Verify listener is registered:**

```php
// Check configuration
var_dump($container->get('config')['listeners'] ?? []);
```

**Ensure listener factory is defined:**

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

**For AbstractListenerAggregate, verify attach() method:**

```php
public function attach(EventManagerInterface $events, $priority = 1): void
{
    // Must store in $this->listeners for proper cleanup
    $this->listeners[] = $events->attach(
        PreHandleEvent::NAME,
        [$this, 'onPreHandle'],
        $priority
    );
}
```

**Check event name matches:**

```php
// Correct
$events->attach(PreHandleEvent::NAME, [$this, 'handler']);

// Wrong
$events->attach('wrong.event.name', [$this, 'handler']);
```

### Middleware Not Executing

**Problem:** Middleware is not running.

**Symptoms:**

- Events are not triggered
- Commands execute but listeners never run

**Solution:**

Verify middleware is registered in the pipeline:

```php
return [
    'PhpCmd\\CmdBus\\ConfigProvider' => [
        'middleware_pipeline' => [
            'pre_handle' => [
                'middleware' => \PhpCmd\Event\Middleware\PreHandleMiddleware::class,
                'priority' => 100,
            ],
            'post_handle' => [
                'middleware' => \PhpCmd\Event\Middleware\PostHandleMiddleware::class,
                'priority' => -100,
            ],
        ],
    ],
];
```

## Event Listener Issues

### Listener Receives Null Event Target

**Problem:** `$event->getTarget()` returns null.

**Symptoms:**

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $target = $event->getTarget(); // null
}
```

**Solution:**

This is expected for `PostHandleEvent` when the handler doesn't return a `CommandResult`. Check if target is null before using:

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $target = $event->getTarget();

    if ($target === null) {
        return; // Or handle accordingly
    }

    // Process target
}
```

Or ensure your handlers return `CommandResult`:

```php
public function handle(CommandInterface $command): CommandResult
{
    $result = $this->doWork($command);

    return new CommandResult($command, $result);
}
```

### Type Errors in Listeners

**Problem:** Type errors when accessing event properties.

**Symptoms:**

```text
TypeError: PhpCmd\Event\PreHandleEvent::getTarget():
Return value must be of type PhpCmd\CmdBus\CommandInterface
```

**Solution:**

Ensure commands implement the correct interface:

```php
use PhpCmd\CmdBus\CommandInterface;

final class MyCommand implements CommandInterface
{
    // Implementation
}
```

### Listener Priority Not Working

**Problem:** Listeners execute in wrong order.

**Symptoms:**

- Validation runs after logging
- Authorization happens too late

**Solution:**

**Check priority values** (higher numbers = earlier execution):

```php
return [
    'listeners' => [
        [
            'listener' => AuthListener::class,
            'priority' => 1000, // Runs first
        ],
        [
            'listener' => LogListener::class,
            'priority' => 100, // Runs later
        ],
    ],
];
```

**For aggregates, pass priority correctly:**

```php
public function attach(EventManagerInterface $events, $priority = 1): void
{
    // Use the passed priority
    $this->listeners[] = $events->attach(
        PreHandleEvent::NAME,
        [$this, 'handler'],
        $priority // Not hardcoded!
    );
}
```

## Middleware Problems

### EventManager Not Injected

**Problem:** EventManager is null in middleware.

**Symptoms:**

```text
Error: Call to a member function triggerEvent() on null
```

**Solution:**

Ensure the `EventManagerAwareDelegator` is configured:

```php
return [
    'dependencies' => [
        'delegators' => [
            \PhpCmd\Event\Middleware\PreHandleMiddleware::class => [
                \PhpCmd\Event\Container\EventManagerAwareDelegator::class,
            ],
            \PhpCmd\Event\Middleware\PostHandleMiddleware::class => [
                \PhpCmd\Event\Container\EventManagerAwareDelegator::class,
            ],
        ],
    ],
];
```

The `ConfigProvider` does this automatically, so ensure it's loaded.

### Custom Middleware Not Working

**Problem:** Custom middleware doesn't receive EventManager.

**Solution:**

**Implement the interface:**

```php
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;

final class MyMiddleware implements
    MiddlewareInterface,
    EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    // ...
}
```

**Register the delegator:**

```php
return [
    'dependencies' => [
        'delegators' => [
            MyMiddleware::class => [
                \PhpCmd\Event\Container\EventManagerAwareDelegator::class,
            ],
        ],
    ],
];
```

## Performance Issues

### Slow Command Execution

**Problem:** Commands execute slowly after adding listeners.

**Diagnosis:**

```php
// Add timing to listener
public function onPreHandle(PreHandleEvent $event): void
{
    $start = microtime(true);

    // Your code

    $duration = microtime(true) - $start;
    error_log("Listener took: {$duration}s");
}
```

**Solutions:**

**Move heavy operations to post-handle:**

```php
// Don't do heavy work in pre-handle
public function onPreHandle(PreHandleEvent $event): void
{
    // Quick validation only
    $this->quickValidate($event->getTarget());
}

// Do heavy work in post-handle or async
public function onPostHandle(PostHandleEvent $event): void
{
    $this->queue->push(new HeavyTask($event));
}
```

**Use lazy loading:**

```php
final class OptimizedListener
{
    private ?ExpensiveService $service = null;

    private function getService(): ExpensiveService
    {
        return $this->service ??= $this->createService();
    }
}
```

**Cache expensive operations:**

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();
    $cacheKey = "validation:{$command::class}";

    if ($this->cache->has($cacheKey)) {
        return; // Skip expensive validation
    }

    $this->validate($command);
    $this->cache->set($cacheKey, true, 300);
}
```

### Memory Leaks

**Problem:** Memory usage increases over time.

**Solution:**

**Detach listeners when done:**

```php
$aggregate->detach($eventManager);
```

**Unset large objects:**

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $data = $this->processLargeDataset($event);
    $this->store($data);

    unset($data); // Free memory
}
```

**Use generators for large datasets:**

```php
private function processItems(array $items): \Generator
{
    foreach ($items as $item) {
        yield $this->process($item);
    }
}
```

## Debugging Tips

### Enable Debug Logging

```php
final class DebugListener
{
    public function onPreHandle(PreHandleEvent $event): void
    {
        error_log(sprintf(
            "PRE: %s\nTrace: %s",
            $event->getTarget()::class,
            (new \Exception())->getTraceAsString()
        ));
    }

    public function onPostHandle(PostHandleEvent $event): void
    {
        error_log("POST: Command completed");
    }
}
```

### Inspect Event Manager

```php
$eventManager = $container->get(EventManagerInterface::class);

// Get all listeners for an event
$listeners = $eventManager->getListeners(PreHandleEvent::NAME);

foreach ($listeners as $listener) {
    var_dump($listener); // Inspect each listener
}
```

### Verify Service Registration

```php
// Check if service exists
var_dump($container->has(MyListener::class)); // Should be true

// Get service
try {
    $listener = $container->get(MyListener::class);
    var_dump($listener);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Test Listener in Isolation

```php
final class ListenerDebugTest extends TestCase
{
    public function testListenerWorks(): void
    {
        $listener = new MyListener(/* dependencies */);
        $command = new TestCommand();
        $event = new PreHandleEvent($command);

        // Should not throw
        $listener->onPreHandle($event);

        $this->assertTrue(true);
    }
}
```

### Use PHPStan/Psalm

Run static analysis to catch type errors:

```bash
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse src/
```

### Enable Error Reporting

```php
// In development
error_reporting(E_ALL);
ini_set('display_errors', '1');
```

## Getting Help

If you're still having issues:

1. **Check the examples:** See [Examples](./examples) for working code
2. **Review documentation:** Re-read relevant sections
3. **Search issues:** Check [GitHub Issues](https://github.com/php-cmd/laminas-events/issues)
4. **Ask for help:** Open a new issue with:
   - PHP version
   - Package version
   - Full error message
   - Minimal reproduction code

## See Also

- [Getting Started](./getting-started.md)
- [Configuration Guide](./configuration.md)
- [API Reference](./api-reference.md)
- [Examples](./examples)
