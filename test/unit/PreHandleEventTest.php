<?php

declare(strict_types=1);

namespace PhpCmd\EventTest;

use PhpCmd\CmdBus\Command\NamedCommandInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\Event\PreHandleEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PreHandleEvent::class)]
final class PreHandleEventTest extends TestCase
{
    public function testConstructorSetsEventName(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $event   = new PreHandleEvent($command);

        $this->assertEquals(PreHandleEvent::NAME, $event->getName());
    }

    public function testConstructorSetsTarget(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $event   = new PreHandleEvent($command);

        $this->assertSame($command, $event->getTarget());
    }

    public function testConstructorWithParams(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $params  = ['key1' => 'value1', 'key2' => 'value2'];
        $event   = new PreHandleEvent($command, $params);

        $this->assertEquals($params, $event->getParams());
    }

    public function testConstructorWithNullParams(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $event   = new PreHandleEvent($command, null);

        $this->assertEquals([], $event->getParams());
    }

    public function testConstructorWithEmptyParams(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $event   = new PreHandleEvent($command, []);

        $this->assertEquals([], $event->getParams());
    }

    public function testGetTargetReturnsCommand(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $event   = new PreHandleEvent($command);

        $this->assertSame($command, $event->getTarget());
    }

    public function testGetTargetReturnsNamedCommand(): void
    {
        $command = $this->createMock(NamedCommandInterface::class);
        $event   = new PreHandleEvent($command);

        $result = $event->getTarget();
        $this->assertSame($command, $result);
        $this->assertInstanceOf(NamedCommandInterface::class, $result);
    }

    public function testEventNameIsConstant(): void
    {
        $this->assertEquals('pre.handle', PreHandleEvent::NAME);
    }

    public function testGetParamReturnsSpecificParam(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $params  = ['key1' => 'value1', 'key2' => 'value2'];
        $event   = new PreHandleEvent($command, $params);

        $this->assertEquals('value1', $event->getParam('key1'));
        $this->assertEquals('value2', $event->getParam('key2'));
    }

    public function testSetParamUpdatesParam(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $event   = new PreHandleEvent($command);

        $event->setParam('testKey', 'testValue');

        $this->assertEquals('testValue', $event->getParam('testKey'));
    }
}
