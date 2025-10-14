<?php

declare(strict_types=1);

namespace PhpCmd\EventTest;

use PhpCmd\CmdBus\Command\CommandResultInterface;
use PhpCmd\Event\PostHandleEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PostHandleEvent::class)]
final class PostHandleEventTest extends TestCase
{
    public function testConstructorSetsEventName(): void
    {
        $result = $this->createMock(CommandResultInterface::class);
        $event  = new PostHandleEvent($result);

        $this->assertEquals(PostHandleEvent::NAME, $event->getName());
    }

    public function testConstructorSetsTarget(): void
    {
        $result = $this->createMock(CommandResultInterface::class);
        $event  = new PostHandleEvent($result);

        $this->assertSame($result, $event->getTarget());
    }

    public function testConstructorWithNullTarget(): void
    {
        $event = new PostHandleEvent(null);

        $this->assertNull($event->getTarget());
        $this->assertEquals(PostHandleEvent::NAME, $event->getName());
    }

    public function testConstructorWithParams(): void
    {
        $result = $this->createMock(CommandResultInterface::class);
        $params = ['key1' => 'value1', 'key2' => 'value2'];
        $event  = new PostHandleEvent($result, $params);

        $this->assertEquals($params, $event->getParams());
    }

    public function testConstructorWithNullParams(): void
    {
        $result = $this->createMock(CommandResultInterface::class);
        $event  = new PostHandleEvent($result, null);

        $this->assertEquals([], $event->getParams());
    }

    public function testConstructorWithEmptyParams(): void
    {
        $result = $this->createMock(CommandResultInterface::class);
        $event  = new PostHandleEvent($result, []);

        $this->assertEquals([], $event->getParams());
    }

    public function testConstructorWithNullTargetAndParams(): void
    {
        $params = ['key1' => 'value1'];
        $event  = new PostHandleEvent(null, $params);

        $this->assertNull($event->getTarget());
        $this->assertEquals($params, $event->getParams());
    }

    public function testEventNameIsConstant(): void
    {
        $this->assertEquals('post.handle', PostHandleEvent::NAME);
    }

    public function testGetParamReturnsSpecificParam(): void
    {
        $result = $this->createMock(CommandResultInterface::class);
        $params = ['key1' => 'value1', 'key2' => 'value2'];
        $event  = new PostHandleEvent($result, $params);

        $this->assertEquals('value1', $event->getParam('key1'));
        $this->assertEquals('value2', $event->getParam('key2'));
    }

    public function testSetParamUpdatesParam(): void
    {
        $result = $this->createMock(CommandResultInterface::class);
        $event  = new PostHandleEvent($result);

        $event->setParam('testKey', 'testValue');

        $this->assertEquals('testValue', $event->getParam('testKey'));
    }
}
