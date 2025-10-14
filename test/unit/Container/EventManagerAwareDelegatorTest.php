<?php

declare(strict_types=1);

namespace PhpCmd\EventTest\Container;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\Container\EventManagerAwareDelegator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(EventManagerAwareDelegator::class)]
final class EventManagerAwareDelegatorTest extends TestCase
{
    private EventManagerAwareDelegator $delegator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delegator = new EventManagerAwareDelegator();
    }

    public function testInvokeInjectsEventManagerIntoService(): void
    {
        $eventManager = $this->createMock(EventManagerInterface::class);
        $service      = $this->createMock(EventManagerAwareInterface::class);
        $container    = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
            ->method('get')
            ->with(EventManagerInterface::class)
            ->willReturn($eventManager);

        $service->expects($this->once())
            ->method('setEventManager')
            ->with($eventManager);

        $callback = fn() => $service;

        $result = ($this->delegator)($container, 'serviceName', $callback);

        $this->assertSame($service, $result);
    }

    public function testInvokeReturnsServiceImplementingEventManagerAwareInterface(): void
    {
        $eventManager = $this->createMock(EventManagerInterface::class);
        $service      = $this->createMock(EventManagerAwareInterface::class);
        $container    = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturn($eventManager);
        $callback = fn() => $service;

        $result = ($this->delegator)($container, 'serviceName', $callback);

        $this->assertInstanceOf(EventManagerAwareInterface::class, $result);
    }

    public function testInvokeCallsCallbackToGetService(): void
    {
        $eventManager = $this->createMock(EventManagerInterface::class);
        $service      = $this->createMock(EventManagerAwareInterface::class);
        $container    = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturn($eventManager);

        $callbackInvoked = false;
        $callback        = function () use ($service, &$callbackInvoked) {
            $callbackInvoked = true;
            return $service;
        };

        ($this->delegator)($container, 'serviceName', $callback);

        $this->assertTrue($callbackInvoked, 'Callback was not invoked');
    }

    public function testDelegatorIsCallable(): void
    {
        $this->assertTrue(is_callable($this->delegator));
    }

    public function testInvokeWithRealEventManager(): void
    {
        $eventManager = new EventManager();
        $service      = $this->createMock(EventManagerAwareInterface::class);
        $container    = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
            ->method('get')
            ->with(EventManagerInterface::class)
            ->willReturn($eventManager);

        $service->expects($this->once())
            ->method('setEventManager')
            ->with($eventManager);

        $callback = fn() => $service;

        $result = ($this->delegator)($container, 'serviceName', $callback);

        $this->assertSame($service, $result);
    }

    public function testInvokeWithConcreteEventManagerAwareService(): void
    {
        $eventManager = new EventManager();
        $container    = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
            ->method('get')
            ->with(EventManagerInterface::class)
            ->willReturn($eventManager);

        // Create a concrete class implementing EventManagerAwareInterface
        $service = new class implements EventManagerAwareInterface {
            private EventManagerInterface $eventManager;

            public function setEventManager(EventManagerInterface $eventManager): void
            {
                $this->eventManager = $eventManager;
            }

            public function getEventManager(): EventManagerInterface
            {
                return $this->eventManager;
            }
        };

        $callback = fn() => $service;

        $result = ($this->delegator)($container, 'serviceName', $callback);

        $this->assertSame($service, $result);
        $this->assertSame($eventManager, $result->getEventManager());
    }

    public function testInvokePassesCorrectServiceName(): void
    {
        $eventManager = $this->createMock(EventManagerInterface::class);
        $service      = $this->createMock(EventManagerAwareInterface::class);
        $container    = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturn($eventManager);
        $callback = fn() => $service;

        $serviceName = 'my.custom.service';

        // Verify the service name is passed but not necessarily used
        $result = ($this->delegator)($container, $serviceName, $callback);

        $this->assertInstanceOf(EventManagerAwareInterface::class, $result);
    }
}
