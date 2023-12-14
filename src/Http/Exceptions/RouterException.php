<?php declare(strict_types=1);
namespace Phx\Http\Exceptions;

use Throwable;

/**
 * Exception caused when route executor encounters problems processing actions.
 */
class RouterException extends HttpException
{
    public function __construct(
        string $message = "", ?Throwable $previous = null
    ) {
        parent::__construct($message, 500, $previous);
    }
}