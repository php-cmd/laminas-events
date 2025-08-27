<?php

declare(strict_types=1);

namespace PhpCmd\Event\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

final class InvalidServiceException extends RuntimeException implements ContainerExceptionInterface
{
}
