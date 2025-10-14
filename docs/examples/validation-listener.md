# Validation Listener Example

This example demonstrates command validation before execution.

## Use Case

Validate commands using a validation service before they are executed, throwing an exception if validation fails.

## Code

### 1. Define the Validation Interface

```php
<?php

declare(strict_types=1);

namespace App\Validation;

use PhpCmd\CmdBus\CommandInterface;

interface ValidatorInterface
{
    public function validate(CommandInterface $command): array;
    public function isValid(CommandInterface $command): bool;
}
```

### 2. Create the Listener

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use App\Exception\ValidationException;
use App\Validation\ValidatorInterface;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\PreHandleEvent;
use Psr\Log\LoggerInterface;

final class ValidationListener extends AbstractListenerAggregate
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {}

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            PreHandleEvent::NAME,
            [$this, 'validate'],
            $priority
        );
    }

    public function validate(PreHandleEvent $event): void
    {
        $command = $event->getTarget();

        $this->logger->debug('Validating command', [
            'command' => $command::class,
        ]);

        $errors = $this->validator->validate($command);

        if (!empty($errors)) {
            $this->logger->warning('Command validation failed', [
                'command' => $command::class,
                'errors' => $errors,
            ]);

            throw new ValidationException(
                'Command validation failed',
                $errors
            );
        }

        $this->logger->debug('Command validation passed', [
            'command' => $command::class,
        ]);
    }
}
```

### 3. Create the Exception

```php
<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly array $errors = []
    ) {
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

### 4. Create a Simple Validator Implementation

```php
<?php

declare(strict_types=1);

namespace App\Validation;

use PhpCmd\CmdBus\CommandInterface;

final class CommandValidator implements ValidatorInterface
{
    public function validate(CommandInterface $command): array
    {
        $errors = [];
        $reflection = new \ReflectionClass($command);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($command);

            // Simple validation rules
            if ($value === null) {
                $errors[$property->getName()] = 'Value cannot be null';
            }

            if (is_string($value) && trim($value) === '') {
                $errors[$property->getName()] = 'Value cannot be empty';
            }

            // Email validation
            if ($property->getName() === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email address';
            }
        }

        return $errors;
    }

    public function isValid(CommandInterface $command): bool
    {
        return empty($this->validate($command));
    }
}
```

### 5. Create the Factory

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use App\Validation\ValidatorInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class ValidationListenerFactory
{
    public function __invoke(ContainerInterface $container): ValidationListener
    {
        return new ValidationListener(
            $container->get(ValidatorInterface::class),
            $container->get(LoggerInterface::class)
        );
    }
}
```

### 6. Configure Services

```php
<?php

declare(strict_types=1);

// config/autoload/validation.global.php

return [
    'dependencies' => [
        'factories' => [
            \App\Listener\ValidationListener::class =>
                \App\Listener\ValidationListenerFactory::class,
            \App\Validation\ValidatorInterface::class =>
                fn() => new \App\Validation\CommandValidator(),
        ],
    ],
    'listeners' => [
        [
            'listener' => \App\Listener\ValidationListener::class,
            'priority' => 800, // High priority - validate early
        ],
    ],
];
```

## Usage

### Valid Command

```php
<?php

use PhpCmd\CmdBus\CmdBusInterface;
use App\Command\CreateUserCommand;

$cmdBus = $container->get(CmdBusInterface::class);

try {
    // Valid command - will execute
    $result = $cmdBus->handle(new CreateUserCommand(
        '[email protected]',
        'John Doe'
    ));

    echo "User created successfully\n";
} catch (\App\Exception\ValidationException $e) {
    echo "Validation failed: " . $e->getMessage() . "\n";
    print_r($e->getErrors());
}
```

### Invalid Command

```php
<?php

try {
    // Invalid command - empty name
    $result = $cmdBus->handle(new CreateUserCommand(
        '[email protected]',
        '' // Empty name!
    ));
} catch (\App\Exception\ValidationException $e) {
    echo "Validation failed: " . $e->getMessage() . "\n";
    print_r($e->getErrors());

    // Output:
    // Validation failed: Command validation failed
    // Array
    // (
    //     [name] => Value cannot be empty
    // )
}
```

## Advanced: Symfony Validator Integration

### Using Symfony Validator

```php
<?php

declare(strict_types=1);

namespace App\Validation;

use PhpCmd\CmdBus\CommandInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class SymfonyCommandValidator implements ValidatorInterface
{
    public function __construct(
        private readonly SymfonyValidator $validator
    ) {}

    public function validate(CommandInterface $command): array
    {
        $violations = $this->validator->validate($command);
        $errors = [];

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $errors;
    }

    public function isValid(CommandInterface $command): bool
    {
        return count($this->validator->validate($command)) === 0;
    }
}
```

### Command with Constraints

```php
<?php

declare(strict_types=1);

namespace App\Command;

use PhpCmd\CmdBus\Command\NamedCommandInterface;
use PhpCmd\CmdBus\Command\NamedCommandTrait;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public readonly string $name
    ) {}
}
```

## Testing

```php
<?php

declare(strict_types=1);

namespace AppTest\Listener;

use App\Exception\ValidationException;
use App\Listener\ValidationListener;
use App\Validation\ValidatorInterface;
use PhpCmd\Event\PreHandleEvent;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ValidationListenerTest extends TestCase
{
    private ValidatorInterface $validator;
    private LoggerInterface $logger;
    private ValidationListener $listener;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new ValidationListener(
            $this->validator,
            $this->logger
        );
    }

    public function testValidCommandPasses(): void
    {
        $command = new TestCommand();
        $event = new PreHandleEvent($command);

        $this->validator
            ->method('validate')
            ->willReturn([]); // No errors

        // Should not throw
        $this->listener->validate($event);
        $this->assertTrue(true);
    }

    public function testInvalidCommandThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Command validation failed');

        $command = new TestCommand();
        $event = new PreHandleEvent($command);

        $this->validator
            ->method('validate')
            ->willReturn(['field' => 'error message']);

        $this->listener->validate($event);
    }

    public function testLogsValidationFailure(): void
    {
        $command = new TestCommand();
        $event = new PreHandleEvent($command);

        $this->validator
            ->method('validate')
            ->willReturn(['field' => 'error']);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Command validation failed', $this->isType('array'));

        try {
            $this->listener->validate($event);
        } catch (ValidationException $e) {
            // Expected
        }
    }
}
```

## Integration Testing

```php
<?php

declare(strict_types=1);

namespace AppTest\Integration;

use App\Exception\ValidationException;
use PhpCmd\CmdBus\CmdBusInterface;
use PHPUnit\Framework\TestCase;

final class ValidationIntegrationTest extends TestCase
{
    private CmdBusInterface $cmdBus;

    protected function setUp(): void
    {
        $this->cmdBus = $this->createConfiguredCmdBus();
    }

    public function testValidationPreventsInvalidCommandExecution(): void
    {
        $this->expectException(ValidationException::class);

        // Invalid command should not execute
        $this->cmdBus->handle(new InvalidCommand());
    }

    public function testValidCommandExecutes(): void
    {
        $result = $this->cmdBus->handle(new ValidCommand());

        $this->assertNotNull($result);
    }
}
```

## See Also

- [Authorization Example](./authorization.md)
- [Simple Logging Example](./simple-logging.md)
- [Best Practices](../best-practices.md)
