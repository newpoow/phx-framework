<?php declare(strict_types=1);
namespace Phx\Injection\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception caused when no entries were found in the container.
 */
class EntryNotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}