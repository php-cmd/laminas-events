<?php

declare(strict_types=1);

namespace PhpCmd\EventIntegrationTest\TestAsset;

use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\CommandHandlerInterface;

final class TestCommandHandler implements CommandHandlerInterface
{
    public const HANDLER_RESULT = 'handler_result';

    public function handle(CommandInterface $command): mixed
    {
        return self::HANDLER_RESULT;
    }
}
