<?php

declare(strict_types=1);

namespace PhpCmd\EventIntegrationTest\TestAsset;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use PhpCmd\CmdBus\Command\CommandResultInterface;
use PhpCmd\CmdBus\Command\NamedCommandInterface;
use PhpCmd\Event\PostHandleEvent;
use PhpCmd\Event\PreHandleEvent;

final class BusEventListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(PreHandleEvent::NAME, [$this, 'onPreHandle'], $priority);

        $this->listeners[] = $events->attach(PostHandleEvent::NAME, [$this, 'onPostHandle'], $priority);
    }

    public function onPreHandle(PreHandleEvent $event): string
    {
        return $event->getTarget()->getName();
    }

    public function onPostHandle(PostHandleEvent $event): string
    {
        /** @var CommandResultInterface $result */
        $result = $event->getTarget();
        /** @var NamedCommandInterface $command */
        $command = $result->getCommand();
        return $command->getName();
    }
}
