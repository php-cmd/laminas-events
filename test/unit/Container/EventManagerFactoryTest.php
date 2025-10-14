<?php

declare(strict_types=1);

namespace PhpCmd\EventTest\Container;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use PhpCmd\Event\Container\EventManagerFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(EventManagerFactory::class)]
final class EventManagerFactoryTest extends TestCase
{
    private EventManagerFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new EventManagerFactory();
    }

    public function testInvokeReturnsEventManager(): void
    {
        $sharedEventManager = new SharedEventManager();
        $container          = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
            ->method('get')
            ->with(SharedEventManagerInterface::class)
            ->willReturn($sharedEventManager);

        $result = ($this->factory)($container);

        $this->assertInstanceOf(EventManager::class, $result);
    }

    public function testInvokeInjectsSharedEventManager(): void
    {
        $sharedEventManager = new SharedEventManager();
        $container          = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
            ->method('get')
            ->with(SharedEventManagerInterface::class)
            ->willReturn($sharedEventManager);

        $eventManager = ($this->factory)($container);

        $this->assertSame($sharedEventManager, $eventManager->getSharedManager());
    }

    public function testInvokeWithMockedSharedEventManager(): void
    {
        $sharedEventManager = $this->createMock(SharedEventManagerInterface::class);
        $container          = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
            ->method('get')
            ->with(SharedEventManagerInterface::class)
            ->willReturn($sharedEventManager);

        $eventManager = ($this->factory)($container);

        $this->assertInstanceOf(EventManager::class, $eventManager);
        $this->assertSame($sharedEventManager, $eventManager->getSharedManager());
    }

    public function testMultipleInvocationsCreateDifferentInstances(): void
    {
        $sharedEventManager = new SharedEventManager();
        $container          = $this->createMock(ContainerInterface::class);

        $container->method('get')
            ->with(SharedEventManagerInterface::class)
            ->willReturn($sharedEventManager);

        $eventManager1 = ($this->factory)($container);
        $eventManager2 = ($this->factory)($container);

        $this->assertNotSame($eventManager1, $eventManager2);
        $this->assertSame($sharedEventManager, $eventManager1->getSharedManager());
        $this->assertSame($sharedEventManager, $eventManager2->getSharedManager());
    }
}
