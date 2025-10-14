<?php

declare(strict_types=1);

namespace PhpCmd\EventTest\Middleware;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\CmdBus\Command\CommandResult;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;
use PhpCmd\Event\Middleware\PostHandleMiddleware;
use PhpCmd\Event\PostHandleEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PostHandleMiddleware::class)]
final class PostHandleMiddlewareTest extends TestCase
{
    private PostHandleMiddleware $middleware;
    private EventManagerInterface $eventManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware   = new PostHandleMiddleware();
        $this->eventManager = new EventManager();
        $this->middleware->setEventManager($this->eventManager);
    }

    public function testProcessWithCommandResult(): void
    {
        $originalCommand = $this->createMock(CommandInterface::class);
        $commandResult   = $this->createMock(CommandResult::class);
        $commandResult->method('getResult')->willReturn('expected_result');

        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $eventTriggered = false;
        $this->eventManager->attach(PostHandleEvent::NAME, function ($event) use (&$eventTriggered, $commandResult) {
            $eventTriggered = true;
            $this->assertInstanceOf(PostHandleEvent::class, $event);
            $this->assertSame($commandResult, $event->getTarget());
        });

        $result = $this->middleware->process($commandResult, $handler);

        $this->assertTrue($eventTriggered, 'PostHandleEvent was not triggered');
        $this->assertEquals('expected_result', $result);
    }

    public function testProcessWithNonCommandResult(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($command)
            ->willReturn('handler_result');

        $eventTriggered = false;
        $this->eventManager->attach(PostHandleEvent::NAME, function () use (&$eventTriggered) {
            $eventTriggered = true;
        });

        $result = $this->middleware->process($command, $handler);

        $this->assertFalse($eventTriggered, 'PostHandleEvent should not be triggered for non-CommandResult');
        $this->assertEquals('handler_result', $result);
    }

    public function testProcessCallsHandlerWhenNotCommandResult(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($command)
            ->willReturn('result');

        $result = $this->middleware->process($command, $handler);

        $this->assertEquals('result', $result);
    }

    public function testProcessReturnsResultFromCommandResult(): void
    {
        $expectedResult = ['data' => 'value'];
        $commandResult  = $this->createMock(CommandResult::class);
        $commandResult->method('getResult')->willReturn($expectedResult);

        $handler = $this->createMock(CommandHandlerInterface::class);

        $result = $this->middleware->process($commandResult, $handler);

        $this->assertEquals($expectedResult, $result);
    }

    public function testSetEventManager(): void
    {
        $eventManager = $this->createMock(EventManagerInterface::class);
        $middleware   = new PostHandleMiddleware();

        $middleware->setEventManager($eventManager);

        $this->assertSame($eventManager, $middleware->getEventManager());
    }

    public function testGetEventManager(): void
    {
        $middleware = new PostHandleMiddleware();
        $middleware->setEventManager($this->eventManager);

        $this->assertSame($this->eventManager, $middleware->getEventManager());
    }

    public function testEventManagerAwareInterface(): void
    {
        $middleware = new PostHandleMiddleware();

        $this->assertInstanceOf(EventManagerAwareInterface::class, $middleware);
    }

    public function testMiddlewareInterface(): void
    {
        $middleware = new PostHandleMiddleware();

        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testProcessWithMultipleEventListeners(): void
    {
        $commandResult = $this->createMock(CommandResult::class);
        $commandResult->method('getResult')->willReturn('result');

        $handler = $this->createMock(CommandHandlerInterface::class);

        $listener1Called = false;
        $listener2Called = false;

        $this->eventManager->attach(PostHandleEvent::NAME, function () use (&$listener1Called) {
            $listener1Called = true;
        });

        $this->eventManager->attach(PostHandleEvent::NAME, function () use (&$listener2Called) {
            $listener2Called = true;
        });

        $this->middleware->process($commandResult, $handler);

        $this->assertTrue($listener1Called, 'First listener was not called');
        $this->assertTrue($listener2Called, 'Second listener was not called');
    }

    public function testProcessEventContainsCorrectTarget(): void
    {
        $commandResult = $this->createMock(CommandResult::class);
        $commandResult->method('getResult')->willReturn('result');

        $handler = $this->createMock(CommandHandlerInterface::class);

        $capturedTarget = null;
        $this->eventManager->attach(PostHandleEvent::NAME, function ($event) use (&$capturedTarget) {
            if ($event instanceof PostHandleEvent) {
                $capturedTarget = $event->getTarget();
            }
        });

        $this->middleware->process($commandResult, $handler);

        $this->assertSame($commandResult, $capturedTarget);
    }

    public function testProcessWithNullCommandResult(): void
    {
        $commandResult = $this->createMock(CommandResult::class);
        $commandResult->method('getResult')->willReturn(null);

        $handler = $this->createMock(CommandHandlerInterface::class);

        $result = $this->middleware->process($commandResult, $handler);

        $this->assertNull($result);
    }
}
