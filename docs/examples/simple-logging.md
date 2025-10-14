# Simple Logging Example

This example demonstrates basic event logging for command execution.

## Use Case

Log every command execution with timestamp and user context.

## Code

### 1. Create the Listener

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\CmdBus\Command\NamedCommandInterface;
use PhpCmd\Event\PreHandleEvent;
use PhpCmd\Event\PostHandleEvent;
use Psr\Log\LoggerInterface;

final class CommandLoggingListener extends AbstractListenerAggregate
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
        $commandName = $command instanceof NamedCommandInterface
            ? $command->getName()
            : $command::class;

        $this->logger->info('Command execution started', [
            'command' => $commandName,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    public function logPostHandle(PostHandleEvent $event): void
    {
        $this->logger->info('Command execution completed', [
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
}
```

### 2. Create the Factory

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class CommandLoggingListenerFactory
{
    public function __invoke(ContainerInterface $container): CommandLoggingListener
    {
        return new CommandLoggingListener(
            $container->get(LoggerInterface::class)
        );
    }
}
```

### 3. Configure the Listener

```php
<?php

declare(strict_types=1);

// config/autoload/listeners.global.php

return [
    'dependencies' => [
        'factories' => [
            \App\Listener\CommandLoggingListener::class =>
                \App\Listener\CommandLoggingListenerFactory::class,
        ],
    ],
    'listeners' => [
        [
            'listener' => \App\Listener\CommandLoggingListener::class,
            'priority' => 50, // Standard logging priority
        ],
    ],
];
```

## Usage

```php
<?php

use PhpCmd\CmdBus\CmdBusInterface;
use App\Command\CreateUserCommand;

$cmdBus = $container->get(CmdBusInterface::class);

// This will trigger logging
$result = $cmdBus->handle(new CreateUserCommand(
    '[email protected]',
    'John Doe'
));
```

## Expected Output

Your log file will contain:

```text
[2025-10-13 10:30:45] app.INFO: Command execution started {"command":"App\\Command\\CreateUserCommand","timestamp":"2025-10-13 10:30:45"}
[2025-10-13 10:30:45] app.INFO: Command execution completed {"timestamp":"2025-10-13 10:30:45"}
```

## Enhancements

### Add Execution Time

```php
private array $startTimes = [];

public function logPreHandle(PreHandleEvent $event): void
{
    $commandId = spl_object_hash($event->getTarget());
    $this->startTimes[$commandId] = microtime(true);

    // ... existing logging
}

public function logPostHandle(PostHandleEvent $event): void
{
    $result = $event->getTarget();

    if ($result instanceof CommandResultInterface) {
        $command = $result->getCommand();
        $commandId = spl_object_hash($command);
        $duration = microtime(true) - ($this->startTimes[$commandId] ?? 0);

        $this->logger->info('Command execution completed', [
            'duration' => round($duration * 1000, 2) . 'ms',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        unset($this->startTimes[$commandId]);
    }
}
```

### Add User Context

```php
public function __construct(
    private readonly LoggerInterface $logger,
    private readonly AuthServiceInterface $authService
) {}

public function logPreHandle(PreHandleEvent $event): void
{
    $command = $event->getTarget();
    $commandName = $command instanceof NamedCommandInterface
        ? $command->getName()
        : $command::class;

    $this->logger->info('Command execution started', [
        'command' => $commandName,
        'user_id' => $this->authService->getCurrentUserId(),
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
}
```

## Testing

```php
<?php

declare(strict_types=1);

namespace AppTest\Listener;

use App\Listener\CommandLoggingListener;
use PhpCmd\Event\PreHandleEvent;
use PhpCmd\Event\PostHandleEvent;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CommandLoggingListenerTest extends TestCase
{
    private LoggerInterface $logger;
    private CommandLoggingListener $listener;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new CommandLoggingListener($this->logger);
    }

    public function testLogsPreHandle(): void
    {
        $command = new TestCommand();
        $event = new PreHandleEvent($command);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Command execution started', $this->isType('array'));

        $this->listener->logPreHandle($event);
    }

    public function testLogsPostHandle(): void
    {
        $event = new PostHandleEvent();

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Command execution completed', $this->isType('array'));

        $this->listener->logPostHandle($event);
    }
}
```

## See Also

- [Audit Trail Example](./audit-trail.md)
- [Metrics Collection Example](./metrics-collection.md)
- [Events Reference](../events.md)
