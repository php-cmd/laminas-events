<?php

declare(strict_types=1);

namespace PhpCmd\Event\Middleware;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;
use Override;
use PhpCmd\CmdBus\Command\CommandResult;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;
use PhpCmd\Event\PostHandleEvent;

final class PostHandleMiddleware implements MiddlewareInterface, EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    #[Override]
    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        // Custom processing logic for this middleware
        if ($command instanceof CommandResult) {
            $this->getEventManager()->triggerEvent(new PostHandleEvent($command));
            // Return the result of the command
            return $command->getResult();
        }
        return $handler->handle($command);
    }
}
