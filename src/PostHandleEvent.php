<?php

declare(strict_types=1);

namespace PhpCmd\Event;

use Laminas\EventManager\Event;
use PhpCmd\CmdBus\Command\CommandResultInterface;

final class PostHandleEvent extends Event
{
    public const NAME = 'post.handle';

    public function __construct(?CommandResultInterface $target = null, ?array $params = [])
    {
        parent::__construct(self::NAME, $target, $params);
    }
}
