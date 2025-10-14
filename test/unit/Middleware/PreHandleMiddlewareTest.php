<?php

declare(strict_types=1);

namespace PhpCmd\EventTest\Middleware;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ResponseCollection;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\Event\Middleware\PreHandleMiddleware;
use PhpCmd\Event\PreHandleEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PreHandleMiddleware::class)]
final class PreHandleMiddlewareTest extends TestCase
{
    private PreHandleMiddleware $middleware;
    private EventManagerInterface $eventManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware   = new PreHandleMiddleware();
        $this->eventManager = new EventManager();
        $this->middleware->setEventManager($this->eventManager);
    }

    public function testProcessTriggersPreHandleEvent(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($command)
            ->willReturn('result');

        $eventTriggered = false;
        $this->eventManager->attach(PreHandleEvent::NAME, function ($event) use (&$eventTriggered, $command) {
            $eventTriggered = true;
            $this->assertInstanceOf(PreHandleEvent::class, $event);
            $this->assertSame($command, $event->getTarget());
        });

        $result = $this->middleware->process($command, $handler);

        $this->assertTrue($eventTriggered, 'PreHandleEvent was not triggered');
        $this->assertEquals('result', $result);
    }

    public function testProcessCallsHandler(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($command)
            ->willReturn('expected_result');

        $result = $this->middleware->process($command, $handler);

        $this->assertEquals('expected_result', $result);
    }

    public function testProcessReturnsHandlerResult(): void
    {
        $command       = $this->createMock(CommandInterface::class);
        $handler       = $this->createMock(CommandHandlerInterface::class);
        $expectedValue = ['some' => 'data'];

        $handler->expects($this->once())
            ->method('handle')
            ->with($command)
            ->willReturn($expectedValue);

        $result = $this->middleware->process($command, $handler);

        $this->assertEquals($expectedValue, $result);
    }

    public function testSetEventManager(): void
    {
        $eventManager = $this->createMock(EventManagerInterface::class);
        $middleware   = new PreHandleMiddleware();

        $middleware->setEventManager($eventManager);

        $this->assertSame($eventManager, $middleware->getEventManager());
    }

    public function testGetEventManager(): void
    {
        $middleware = new PreHandleMiddleware();
        $middleware->setEventManager($this->eventManager);

        $this->assertSame($this->eventManager, $middleware->getEventManager());
    }

    public function testEventManagerAwareInterface(): void
    {
        $middleware = new PreHandleMiddleware();

        $this->assertInstanceOf(\Laminas\EventManager\EventManagerAwareInterface::class, $middleware);
    }

    public function testMiddlewareInterface(): void
    {
        $middleware = new PreHandleMiddleware();

        $this->assertInstanceOf(\PhpCmd\CmdBus\MiddlewareInterface::class, $middleware);
    }

    public function testProcessWithMultipleEventListeners(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->method('handle')->willReturn('result');

        $listener1Called = false;
        $listener2Called = false;

        $this->eventManager->attach(PreHandleEvent::NAME, function () use (&$listener1Called) {
            $listener1Called = true;
        });

        $this->eventManager->attach(PreHandleEvent::NAME, function () use (&$listener2Called) {
            $listener2Called = true;
        });

        $this->middleware->process($command, $handler);

        $this->assertTrue($listener1Called, 'First listener was not called');
        $this->assertTrue($listener2Called, 'Second listener was not called');
    }

    public function testProcessEventContainsCorrectTarget(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->method('handle')->willReturn('result');

        $capturedTarget = null;
        $this->eventManager->attach(PreHandleEvent::NAME, function ($event) use (&$capturedTarget) {
            if ($event instanceof PreHandleEvent) {
                $capturedTarget = $event->getTarget();
            }
        });

        $this->middleware->process($command, $handler);

        $this->assertSame($command, $capturedTarget);
    }
}
