<?php declare(strict_types=1);
namespace Phx\Http\Exceptions;

/**
 * Exception caused when response headers have already been sent.
 */
class HeadersAlreadySentException extends HttpException
{
}