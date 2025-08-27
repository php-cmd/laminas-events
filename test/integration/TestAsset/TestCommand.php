<?php

declare(strict_types=1);

namespace PhpCmd\EventIntegrationTest\TestAsset;

use PhpCmd\CmdBus\Command\NamedCommandInterface;
use PhpCmd\CmdBus\Command\NamedCommandTrait;

final class TestCommand implements NamedCommandInterface
{
    use NamedCommandTrait;
}
