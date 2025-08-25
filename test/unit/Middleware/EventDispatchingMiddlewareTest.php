<?php

declare(strict_types=1);

namespace PhpCmd\EventTest\Unit\Middleware;

use Laminas\EventManager\EventManagerInterface;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\Event\Event\CommandErrorEvent;
use PhpCmd\Event\Event\PostCommandEvent;
use PhpCmd\Event\Event\PreCommandEvent;
use PhpCmd\Event\Middleware\EventDispatchingMiddleware;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \PhpCmd\Event\Middleware\EventDispatchingMiddleware
 */
final class EventDispatchingMiddlewareTest extends TestCase
{
    public function testProcessTriggersPreAndPostEvents(): void
    {
        $eventManager = $this->createMock(EventManagerInterface::class);
        $command = $this->createMock(CommandInterface::class);
        $handler = $this->createMock(CommandHandlerInterface::class);
        $expectedResult = 'test result';

        $handler->method('handle')->willReturn($expectedResult);

        $middleware = new EventDispatchingMiddleware($eventManager);

        // Expect pre-command event
        $eventManager->expects($this->exactly(2))
            ->method('triggerEvent')
            ->withConsecutive(
                [$this->isInstanceOf(PreCommandEvent::class)],
                [$this->isInstanceOf(PostCommandEvent::class)]
            );

        $result = $middleware->process($command, $handler);

        $this->assertEquals($expectedResult, $result);
    }

    public function testProcessTriggersErrorEventOnException(): void
    {
        $eventManager = $this->createMock(EventManagerInterface::class);
        $command = $this->createMock(CommandInterface::class);
        $handler = $this->createMock(CommandHandlerInterface::class);
        $exception = new RuntimeException('Test exception');

        $handler->method('handle')->willThrowException($exception);

        $middleware = new EventDispatchingMiddleware($eventManager);

        // Expect pre-command and error events
        $eventManager->expects($this->exactly(2))
            ->method('triggerEvent')
            ->withConsecutive(
                [$this->isInstanceOf(PreCommandEvent::class)],
                [$this->isInstanceOf(CommandErrorEvent::class)]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test exception');

        $middleware->process($command, $handler);
    }
}
