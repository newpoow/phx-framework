<?php declare(strict_types=1);
namespace Phx\Http\Exceptions;

use Throwable;

/**
 * Exception caused when the http verb used is not supported.
 */
class MethodNotAllowedHttpException extends HttpException
{
    public function __construct(
        protected array $allowedMethods, ?Throwable $previous = null
    ) {
        $message = sprintf(
            "The requested resource is not available for the HTTP method. Supported methods: '%s'.",
            strtoupper(implode(', ', $this->getAllowedMethods()))
        );
        parent::__construct($message, 405, $previous);
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}