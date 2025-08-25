<?php

declare(strict_types=1);

namespace PhpCmd\Event\Middleware;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;
use Override;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;
use PhpCmd\Event\PreHandleEvent;

class PreHandleMiddleware implements MiddlewareInterface, EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    #[Override]
    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
    {
        // Let'em know
        $this->getEventManager()->triggerEvent(new PreHandleEvent($command));
        return $handler->handle($command);
    }
}
