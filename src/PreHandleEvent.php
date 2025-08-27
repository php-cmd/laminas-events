<?php

declare(strict_types=1);

namespace PhpCmd\Event;

use Laminas\EventManager\Event;
use PhpCmd\CmdBus\Command\NamedCommandInterface;
use PhpCmd\CmdBus\CommandInterface;

/**
 * @extends Event<CommandInterface|NamedCommandInterface, array<string, mixed>>
 */
final class PreHandleEvent extends Event
{
    public const NAME = 'pre.handle';

    /**
     * @param array<string, mixed>|null $params
     */
    public function __construct(CommandInterface $target, ?array $params = [])
    {
        parent::__construct(self::NAME, $target, $params);
    }

    public function getTarget(): CommandInterface|NamedCommandInterface
    {
        return $this->target;
    }
}
