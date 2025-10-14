<?php

declare(strict_types=1);

namespace PhpCmd\EventTest\Exception;

use Exception;
use PhpCmd\Event\Exception\InvalidServiceException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

use function sprintf;

#[CoversClass(InvalidServiceException::class)]
final class InvalidServiceExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new InvalidServiceException();

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionImplementsContainerExceptionInterface(): void
    {
        $exception = new InvalidServiceException();

        $this->assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }

    public function testExceptionCanBeConstructedWithMessage(): void
    {
        $message   = 'Test exception message';
        $exception = new InvalidServiceException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionCanBeConstructedWithCode(): void
    {
        $code      = 123;
        $exception = new InvalidServiceException('Test message', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionCanBeConstructedWithPrevious(): void
    {
        $previous  = new Exception('Previous exception');
        $exception = new InvalidServiceException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionCanBeThrownAndCaught(): void
    {
        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage('Custom error message');

        throw new InvalidServiceException('Custom error message');
    }

    public function testExceptionCanBeCaughtAsRuntimeException(): void
    {
        try {
            throw new InvalidServiceException('Test');
        } catch (RuntimeException $e) {
            $this->assertInstanceOf(InvalidServiceException::class, $e);
            return;
        }
    }

    public function testExceptionCanBeCaughtAsContainerException(): void
    {
        try {
            throw new InvalidServiceException('Test');
        } catch (ContainerExceptionInterface $e) {
            $this->assertInstanceOf(InvalidServiceException::class, $e);
            return;
        }
    }

    public function testExceptionWithEmptyMessage(): void
    {
        $exception = new InvalidServiceException();

        $this->assertEquals('', $exception->getMessage());
    }

    public function testExceptionWithComplexMessage(): void
    {
        $message   = sprintf(
            'Service %s failed because %s',
            'TestService',
            'it does not implement the required interface'
        );
        $exception = new InvalidServiceException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertStringContainsString('TestService', $exception->getMessage());
    }
}
