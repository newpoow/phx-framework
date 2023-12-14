<?php declare(strict_types=1);
namespace Phx\Configuration\Parsers;

use InvalidArgumentException;
use Phx\Configuration\Exceptions\ParserException;
use Phx\Configuration\ParserInterface;

/**
 * Parser for files with the .php extension.
 */
class PhpParser implements ParserInterface
{
    public function parse(string $file): array
    {
        if (($path = realpath($file)) === false) {
            throw new InvalidArgumentException(sprintf(
                "Couldn't compute the absolute path of '%s'.", $file
            ));
        }

        $content = require $path;
        if (!is_array($content)) {
            throw new ParserException(sprintf(
                "The file '%s' did not return an array.", $file
            ));
        }
        return $content;
    }
}