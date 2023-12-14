<?php declare(strict_types=1);
namespace Phx\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Data Stream Factory.
 */
class StreamFactory implements StreamFactoryInterface
{
    public static function createFromString(string $content): StreamInterface
    {
        return new Stream($content);
    }

    public static function createFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (empty($mode) || !in_array($mode[0], ['r', 'w', 'a', 'x', 'c'])) {
            throw new InvalidArgumentException(sprintf(
                "The mode '%s' is invalid.", $mode
            ));
        }

        set_error_handler(function () use ($filename) {
            throw new RuntimeException(sprintf(
                "The file '%s' cannot be opened.", $filename
            ));
        });
        $resource = fopen($filename, $mode);
        restore_error_handler();

        return new Stream($resource);
    }

    public static function createFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return static::createFromString($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return static::createFromFile($filename, $mode);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return static::createFromResource($resource);
    }
}