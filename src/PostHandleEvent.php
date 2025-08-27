<?php

declare(strict_types=1);

namespace PhpCmd\Event;

use Laminas\EventManager\Event;
use PhpCmd\CmdBus\Command\CommandResultInterface;

/**
 * @extends Event<CommandResultInterface|null, array<string, mixed>>
 */
final class PostHandleEvent extends Event
{
    public const NAME = 'post.handle';

    /**
     * @param array<string, mixed>|null $params
     */
    public function __construct(?CommandResultInterface $target = null, ?array $params = [])
    {
        parent::__construct(self::NAME, $target, $params);
    }
}
