<?php declare(strict_types=1);
namespace Phx\Http\Exceptions;

use Throwable;

/**
 * Exception caused when the server could not find the requested resource.
 */
class NotFoundHttpException extends HttpException
{
    public function __construct(
        protected string $path, ?Throwable $previous = null
    ) {
        $message = sprintf("The requested URL '%s' was not found on this server.", $this->getPath());
        parent::__construct($message, 404, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}