<?php declare(strict_types=1);
namespace Phx\Configuration\Exceptions;

use RuntimeException;

/**
 * Exception caused when there are errors in the schema of the file being parsed.
 */
class ParserException extends RuntimeException
{
}