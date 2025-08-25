# Laminas Events Integration for CmdBus

This package provides integration between the CmdBus library and the Laminas EventManager component, allowing for an event-driven architecture in command bus workflows.

## Features

- **Event Dispatching**: Automatically dispatch events before and after command execution.
- **Event Listeners**: Easily attach listeners to respond to command lifecycle events.
- **Validation and Transformation**: Validate commands and transform results using events.
- **Logging and Monitoring**: Integrate logging and monitoring into command execution.

## Installation

```bash
composer require php-cmd/laminas-events
```

## Usage

### Basic Setup

1. **Register the ConfigProvider**:

```php
use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator([
    \PhpCmd\CmdBus\ConfigProvider::class,
    \PhpCmd\Event\ConfigProvider::class, // note the load order, it must load after CmdBus
]);
```

2. **Attach Event Listeners**:

```php
$eventManager = $container->get(EventManagerInterface::class);

$eventManager->attach(PreCommandEvent::NAME, function(PreCommandEvent $event) {
    // Handle pre-command event
});
```

### Example Command

```php
class CreateUserCommand implements CommandInterface
{
    private string $username;
    private string $email;

    public function __construct(string $username, string $email)
    {
        $this->username = $username;
        $this->email = $email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
```

### Example Listener

```php
class CommandLoggingListener
{
    public function __invoke(CommandEvent $event): void
    {
        // Log command execution
    }
}
```

## Contributing

Contributions are welcome! Please submit a pull request or open an issue for discussion.

## License

This package is licensed under the BSD-3-Clause License.
