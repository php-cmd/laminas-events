# Getting Started

This guide will help you get up and running with the Laminas Events integration for CmdBus.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Basic Setup](#basic-setup)
- [Your First Event Listener](#your-first-event-listener)
- [Next Steps](#next-steps)

## Prerequisites

Before you begin, ensure you have:

- PHP 8.2 or higher installed
- Composer for dependency management
- A PSR-11 compatible container (e.g., Laminas ServiceManager)
- Basic understanding of the Command pattern
- Familiarity with event-driven architecture (helpful but not required)

## Installation

### Step 1: Install via Composer

```bash
composer require php-cmd/laminas-events
```

### Step 2: Register the Configuration Provider

The package uses Laminas ConfigProvider pattern. Register it in your application configuration:

#### Mezzio Applications

If using Laminas Component Installer (included by default), you'll be prompted during installation. Otherwise, add to `config/config.php`:

```php
<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator([
    \PhpCmd\Event\ConfigProvider::class,
    // ... other config providers
]);

return $aggregator->getMergedConfig();
```

#### Laminas MVC Applications

Add to your `config/modules.config.php`:

```php
return [
    'PhpCmd\\Event',
    // ... other modules
];
```

#### Standalone Applications

Merge the configuration manually:

```php
use PhpCmd\Event\ConfigProvider;
use Laminas\ServiceManager\ServiceManager;

$config = [
    'dependencies' => [],
];

$eventConfig = (new ConfigProvider())();
$config = array_merge_recursive($config, $eventConfig);

$container = new ServiceManager($config['dependencies']);
```

## Basic Setup

### Understanding the Components

The package provides three main components:

1. **Events**: `PreHandleEvent` and `PostHandleEvent`
2. **Middleware**: `PreHandleMiddleware` and `PostHandleMiddleware`
3. **Factories and Delegators**: For container integration

### Configuration Structure

The minimal configuration looks like this:

```php
return [
    'dependencies' => [
        'aliases' => [
            // EventManager aliases
        ],
        'factories' => [
            // Event manager factory
        ],
        'delegators' => [
            // Middleware delegators
        ],
    ],
    'PhpCmd\\CmdBus\\ConfigProvider' => [
        'middleware_pipeline' => [
            // Pre and post handle middleware
        ],
        'command_map' => [
            // Your command to handler mappings
        ],
    ],
];
```

The `ConfigProvider` handles all of this automatically.

## Your First Event Listener

Let's create a simple event listener that logs command execution.

### Step 1: Create the Listener

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\PreHandleEvent;
use PhpCmd\Event\PostHandleEvent;
use Psr\Log\LoggerInterface;

final class CommandLogger extends AbstractListenerAggregate
{
    public function __construct(
        private readonly LoggerInterface $logger
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
        $commandName = $command instanceof \PhpCmd\CmdBus\Command\NamedCommandInterface
            ? $command->getName()
            : $command::class;

        $this->logger->info('Executing command', [
            'command' => $commandName,
            'timestamp' => time(),
        ]);
    }

    public function logPostHandle(PostHandleEvent $event): void
    {
        $this->logger->info('Command executed successfully', [
            'timestamp' => time(),
        ]);
    }
}
```

### Step 2: Register the Listener Factory

Create a factory for your listener:

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class CommandLoggerFactory
{
    public function __invoke(ContainerInterface $container): CommandLogger
    {
        return new CommandLogger(
            $container->get(LoggerInterface::class)
        );
    }
}
```

### Step 3: Configure the Listener

Add the listener to your configuration:

```php
// config/autoload/listeners.global.php
return [
    'dependencies' => [
        'factories' => [
            \App\Listener\CommandLogger::class => \App\Listener\CommandLoggerFactory::class,
        ],
    ],
    'listeners' => [
        [
            'listener' => \App\Listener\CommandLogger::class,
            'priority' => 100, // Higher numbers execute first
        ],
    ],
];
```

### Step 4: Test It Out

Create and execute a command:

```php
<?php

use PhpCmd\CmdBus\CmdBusInterface;

$cmdBus = $container->get(CmdBusInterface::class);
$result = $cmdBus->handle(new YourCommand());
```

You should see log entries before and after command execution!

## Verifying the Setup

To verify everything is working correctly:

### 1. Check Container Services

```php
$container->has(\Laminas\EventManager\EventManagerInterface::class); // true
$container->has(\PhpCmd\Event\Middleware\PreHandleMiddleware::class); // true
$container->has(\PhpCmd\Event\Middleware\PostHandleMiddleware::class); // true
```

### 2. Inspect the Event Manager

```php
$eventManager = $container->get(\Laminas\EventManager\EventManagerInterface::class);
$listeners = $eventManager->getListeners(PreHandleEvent::NAME);
// Should contain your registered listeners
```

### 3. Run a Test Command

Create a simple test:

```php
<?php

use PHPUnit\Framework\TestCase;
use PhpCmd\CmdBus\CmdBusInterface;

class SetupTest extends TestCase
{
    public function testEventsAreTrigggered(): void
    {
        $container = /* your container */;
        $cmdBus = $container->get(CmdBusInterface::class);

        $result = $cmdBus->handle(new TestCommand());

        // Verify your listeners were called
        $this->assertNotNull($result);
    }
}
```

## Next Steps

Now that you have the basics working, explore:

- [Configuration Guide](./configuration.md) - Learn about all configuration options
- [Events Reference](./events.md) - Understand the event lifecycle
- [Listener Configuration](./listeners.md) - Advanced listener patterns
- [Best Practices](./best-practices.md) - Recommended patterns
- [Examples](./examples) - Real-world usage examples

## Troubleshooting

If you encounter issues:

1. **Events not firing**: Check that your listener is registered in the configuration
2. **Service not found**: Ensure the ConfigProvider is registered
3. **Priority conflicts**: Review listener priorities in your configuration

See the [Troubleshooting Guide](./troubleshooting.md) for more help.
