<?php

declare(strict_types=1);

namespace PhpCmd\Event;

use Laminas\EventManager\Event;
use PhpCmd\CmdBus\CommandInterface;

final class PreHandleEvent extends Event
{
    public const NAME = 'pre.handle';

    public function __construct(?CommandInterface $target = null, ?array $params = [])
    {
        parent::__construct(self::NAME, $target, $params);
    }
}
