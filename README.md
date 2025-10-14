# Laminas Events Integration for CmdBus

[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-8.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-blue.svg)](https://www.php.net/)

A powerful integration package that brings [Laminas EventManager](https://github.com/laminas/laminas-eventmanager) support to [CmdBus](https://github.com/php-cmd/cmd-bus), enabling event-driven architecture in your command bus applications.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Documentation](#documentation)
- [Configuration](#configuration)
- [Usage Examples](#usage-examples)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Features

- 🎯 **Event-Driven Architecture**: Seamlessly integrate Laminas EventManager with CmdBus
- ⚡ **Pre/Post Command Events**: Trigger events before and after command execution
- 🔧 **Flexible Configuration**: Easy setup with Laminas ServiceManager
- 🎨 **Middleware Pipeline**: Built-in middleware for event triggering
- 🧩 **PSR-11 Compatible**: Works with any PSR-11 container
- 📦 **Zero Configuration**: Works out of the box with sensible defaults
- 🔌 **Extensible**: Easy to customize and extend for your needs

## Requirements

- PHP 8.2, 8.3, 8.4, or 8.5
- [php-cmd/cmd-bus](https://github.com/php-cmd/cmd-bus) ^0.3.0
- [laminas/laminas-eventmanager](https://github.com/laminas/laminas-eventmanager) ^3.13
- PSR-11 Container implementation

## Installation

Install via Composer:

```bash
composer require php-cmd/laminas-events
```

If you're using Laminas Component Installer (automatically included with Mezzio and Laminas MVC applications), you'll be prompted to inject the configuration provider. Select the appropriate option when prompted.

## Quick Start

### 1. Register the Config Provider

If you're using Laminas MVC or Mezzio:

```php
// config/config.php or config/modules.config.php
return [
    'PhpCmd\\Event\\ConfigProvider',
    // ... other config providers
];
```

### 2. Create a Command

```php
use PhpCmd\CmdBus\Command\NamedCommandInterface;
use PhpCmd\CmdBus\Command\NamedCommandTrait;

final class CreateUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public readonly string $email,
        public readonly string $name
    ) {}
}
```

### 3. Create a Command Handler

```php
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;

final class CreateUserHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed
    {
        // Your command handling logic
        return $user;
    }
}
```

### 4. Create an Event Listener

```php
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\PreHandleEvent;
use PhpCmd\Event\PostHandleEvent;

final class UserEventListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            PreHandleEvent::NAME,
            [$this, 'onPreHandle'],
            $priority
        );

        $this->listeners[] = $events->attach(
            PostHandleEvent::NAME,
            [$this, 'onPostHandle'],
            $priority
        );
    }

    public function onPreHandle(PreHandleEvent $event): void
    {
        $command = $event->getTarget();
        // Log, validate, or prepare before command execution
    }

    public function onPostHandle(PostHandleEvent $event): void
    {
        $result = $event->getTarget();
        // Log, notify, or process after command execution
    }
}
```

### 5. Configure the Listener

```php
// config/autoload/listeners.global.php
return [
    'listeners' => [
        [
            'listener' => UserEventListener::class,
            'priority' => 100,
        ],
    ],
];
```

### 6. Execute Commands

```php
$cmdBus = $container->get(CmdBusInterface::class);
$result = $cmdBus->handle(new CreateUserCommand('[email protected]', 'John Doe'));
```

## Documentation

Comprehensive documentation is available in the [docs](./docs) directory:

- **[Getting Started](./docs/getting-started.md)** - Installation and basic setup
- **[Configuration Guide](./docs/configuration.md)** - Detailed configuration options
- **[Events Reference](./docs/events.md)** - Pre and post-handle events explained
- **[Middleware Guide](./docs/middleware.md)** - Understanding the middleware pipeline
- **[Listener Configuration](./docs/listeners.md)** - Setting up event listeners
- **[Best Practices](./docs/best-practices.md)** - Recommended patterns and practices
- **[Advanced Usage](./docs/advanced-usage.md)** - Advanced patterns and techniques
- **[API Reference](./docs/api-reference.md)** - Complete API documentation
- **[Examples](./docs/examples)** - Real-world usage examples
- **[Troubleshooting](./docs/troubleshooting.md)** - Common issues and solutions

## Configuration

The package provides a `ConfigProvider` that registers all necessary services and middleware:

```php
use PhpCmd\Event\ConfigProvider;

$config = (new ConfigProvider())();
```

### Default Configuration

The package automatically:

- Registers EventManager and SharedEventManager
- Configures Pre/Post handle middleware
- Sets up delegator factories for dependency injection
- Provides sensible priority defaults

For detailed configuration options, see the [Configuration Guide](./docs/configuration.md).

## Usage Examples

### Logging Command Execution

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();
    $this->logger->info('Executing command: ' . $command->getName());
}

public function onPostHandle(PostHandleEvent $event): void
{
    $this->logger->info('Command executed successfully');
}
```

### Validating Commands

```php
public function onPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();

    if (!$this->validator->isValid($command)) {
        throw new ValidationException($this->validator->getMessages());
    }
}
```

### Broadcasting Events

```php
public function onPostHandle(PostHandleEvent $event): void
{
    $result = $event->getTarget();
    $this->broadcaster->broadcast('user.created', $result);
}
```

For more examples, see the [Examples Directory](./docs/examples).

## Testing

Run the test suite:

```bash
# Unit tests
composer test

# Integration tests
composer test-integration

# All checks (CS, static analysis, tests)
composer check

# Code coverage
composer test-coverage
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the BSD-3-Clause License - see the [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/php-cmd/laminas-events/issues)
- **Source**: [GitHub Repository](https://github.com/php-cmd/laminas-events)
- **Documentation**: [docs](./docs)

## Credits

Built with ❤️ by the PHP-CMD team.
