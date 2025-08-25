<?php

declare(strict_types=1);

namespace PhpCmd\EventTest\Unit\Event;

use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\Event\Event\PreCommandEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpCmd\Event\Event\PreCommandEvent
 */
final class PreCommandEventTest extends TestCase
{
    public function testEventCreation(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $event = new PreCommandEvent($command);

        $this->assertEquals(PreCommandEvent::NAME, $event->getName());
        $this->assertSame($command, $event->getCommand());
        $this->assertSame($command, $event->getParam('command'));
    }

    public function testEventName(): void
    {
        $this->assertEquals('pre-command', PreCommandEvent::NAME);
    }
}
