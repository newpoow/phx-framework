<?php declare(strict_types=1);
namespace Phx\Http\Exceptions;

use Phx\Http\Message\HttpStatus;
use RuntimeException;
use Throwable;

/**
 * HTTP error exceptions.
 */
abstract class HttpException extends RuntimeException
{
    public function __construct(string $message = "", int $code = 500, Throwable $previous = null)
    {
        if ($code > 599 || $code < 100) {
            $code = 500;
        }

        if (empty($message)) {
            $message = HttpStatus::getPhrase($code);
        }
        parent::__construct($message, $code, $previous);
    }
}