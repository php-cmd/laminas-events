<?php

declare(strict_types=1);

namespace PhpCmd\EventTest\Container;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\Event\Container\ListenerConfigurationDelegator;
use PhpCmd\Event\Exception\InvalidServiceException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(ListenerConfigurationDelegator::class)]
final class ListenerConfigurationDelegatorTest extends TestCase
{
    private ListenerConfigurationDelegator $delegator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delegator = new ListenerConfigurationDelegator();
    }

    public function testInvokeReturnsEventManager(): void
    {
        $eventManager = new EventManager();
        $container    = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(false);

        $callback = fn() => $eventManager;

        $result = ($this->delegator)($container, EventManager::class, $callback);

        $this->assertInstanceOf(EventManager::class, $result);
        $this->assertSame($eventManager, $result);
    }

    public function testInvokeThrowsExceptionWhenNotEventManager(): void
    {
        $notEventManager = new \stdClass();
        $container       = $this->createMock(ContainerInterface::class);

        $callback = fn() => $notEventManager;

        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage('cannot operate on a stdClass instance');

        ($this->delegator)($container, 'serviceName', $callback);
    }

    public function testInvokeWithNoConfigReturnsEventManagerUnmodified(): void
    {
        $eventManager = new EventManager();
        $container    = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $callback = fn() => $eventManager;

        $result = ($this->delegator)($container, EventManager::class, $callback);

        $this->assertSame($eventManager, $result);
    }

    public function testInvokeWithEmptyListenersConfig(): void
    {
        $eventManager = new EventManager();
        $container    = $this->createMock(ContainerInterface::class);

        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn([]);

        $callback = fn() => $eventManager;

        $result = ($this->delegator)($container, EventManager::class, $callback);

        $this->assertSame($eventManager, $result);
    }

    public function testInvokeAttachesListenerAggregateFromContainer(): void
    {
        $eventManager = new EventManager();
        $container    = $this->createMock(ContainerInterface::class);

        $listener = $this->createMock(AbstractListenerAggregate::class);
        $listener->expects($this->once())
            ->method('attach')
            ->with($eventManager, 1);

        $config = [
            'listeners' => [
                [
                    'listener' => 'MyListener',
                    'priority' => 1,
                ],
            ],
        ];

        $container->method('has')
            ->willReturnCallback(fn($key) => match ($key) {
                'config'     => true,
                'MyListener' => true,
                default      => false,
            });

        $container->method('get')
            ->willReturnCallback(fn($key) => match ($key) {
                'config'     => $config,
                'MyListener' => $listener,
                default      => null,
            });

        $callback = fn() => $eventManager;

        ($this->delegator)($container, EventManager::class, $callback);
    }

    public function testInvokeAttachesCallableListenerForSingleEvent(): void
    {
        $eventManager = new EventManager();
        $container    = $this->createMock(ContainerInterface::class);

        $listenerCalled = false;
        $listener       = function () use (&$listenerCalled) {
            $listenerCalled = true;
        };

        $config = [
            'listeners' => [
                [
                    'listener' => $listener,
                    'event'    => 'test.event',
                    'priority' => 10,
                ],
            ],
        ];

        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($config);

        $callback = fn() => $eventManager;

        $result = ($this->delegator)($container, EventManager::class, $callback);

        // Trigger the event to verify listener was attached
        $result->trigger('test.event');

        $this->assertTrue($listenerCalled, 'Listener was not attached and called');
    }

    public function testInvokeAttachesCallableListenerForMultipleEvents(): void
    {
        $eventManager = new EventManager();
        $container    = $this->createMock(ContainerInterface::class);

        $callCount = 0;
        $listener  = function () use (&$callCount) {
            $callCount++;
        };

        $config = [
            'listeners' => [
                [
                    'listener' => $listener,
                    'event'    => ['event.one', 'event.two'],
                    'priority' => 5,
                ],
            ],
        ];

        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($config);

        $callback = fn() => $eventManager;

        $result = ($this->delegator)($container, EventManager::class, $callback);

        // Trigger both events to verify listener was attached to both
        $result->trigger('event.one');
        $result->trigger('event.two');

        $this->assertEquals(2, $callCount, 'Listener was not attached to both events');
    }

    public function testInvokeUsesDefaultPriorityWhenNotSpecified(): void
    {
        $eventManager = new EventManager();
        $container    = $this->createMock(ContainerInterface::class);

        $listener = function () {
        };

        $config = [
            'listeners' => [
                [
                    'listener' => $listener,
                    'event'    => 'test.event',
                    // No priority specified
                ],
            ],
        ];

        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($config);

        $callback = fn() => $eventManager;

        // Should not throw an exception
        $result = ($this->delegator)($container, EventManager::class, $callback);

        $this->assertInstanceOf(EventManager::class, $result);
    }

    public function testInvokeSkipsNonCallableServiceWithoutEvent(): void
    {
        $eventManager = new EventManager();
        $container    = $this->createMock(ContainerInterface::class);

        $config = [
            'listeners' => [
                [
                    'listener' => 'NonCallableService',
                    // No event specified
                ],
            ],
        ];

        $container->method('has')
            ->willReturnCallback(fn($key) => match ($key) {
                'config'             => true,
                'NonCallableService' => true,
                default              => false,
            });

        $container->method('get')
            ->willReturnCallback(fn($key) => match ($key) {
                'config'             => $config,
                'NonCallableService' => new \stdClass(), // Not a listener aggregate
                default              => null,
            });

        $callback = fn() => $eventManager;

        // Should not throw an exception
        $result = ($this->delegator)($container, EventManager::class, $callback);

        $this->assertInstanceOf(EventManager::class, $result);
    }

    public function testInvokeWithMultipleListenersOfDifferentTypes(): void
    {
        $eventManager = new EventManager();
        $container    = $this->createMock(ContainerInterface::class);

        $aggregate = $this->createMock(AbstractListenerAggregate::class);
        $aggregate->expects($this->once())->method('attach');

        $callableListenerCalled = false;
        $callable               = function () use (&$callableListenerCalled) {
            $callableListenerCalled = true;
        };

        $config = [
            'listeners' => [
                [
                    'listener' => 'MyAggregate',
                    'priority' => 10,
                ],
                [
                    'listener' => $callable,
                    'event'    => 'test.event',
                    'priority' => 5,
                ],
            ],
        ];

        $container->method('has')
            ->willReturnCallback(fn($key) => match ($key) {
                'config'      => true,
                'MyAggregate' => true,
                default       => false,
            });

        $container->method('get')
            ->willReturnCallback(fn($key) => match ($key) {
                'config'      => $config,
                'MyAggregate' => $aggregate,
                default       => null,
            });

        $callback = fn() => $eventManager;

        $result = ($this->delegator)($container, EventManager::class, $callback);

        $result->trigger('test.event');

        $this->assertTrue($callableListenerCalled);
    }

    public function testDelegatorIsCallable(): void
    {
        $this->assertTrue(is_callable($this->delegator));
    }

    public function testInvokeWithPrimitiveReturnValueThrowsException(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $callback  = fn() => 'string';

        $this->expectException(InvalidServiceException::class);

        ($this->delegator)($container, 'serviceName', $callback);
    }
}
