<?php declare(strict_types=1);
namespace Phx\Injection\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Exception caused when a dependency cannot be built.
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}