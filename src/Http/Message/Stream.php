<?php declare(strict_types=1);
namespace Phx\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

/**
 * Data stream for display in the message.
 */
class Stream implements StreamInterface
{
    protected $resource;

    public function __construct($resource = '')
    {
        if (is_string($resource)) {
            $stream = fopen('php://temp', 'r+b');
            fwrite($stream, $resource);
            rewind($stream);
            $resource = $stream;
        }

        if (!is_resource($resource)) {
            throw new InvalidArgumentException(
                "Invalid stream resource; must be a string or resource."
            );
        }
        $this->resource = $resource;
    }

    public function close(): void
    {
        if (is_resource($this->resource)) {
            $resource = $this->detach();
            fclose($resource);
        }
    }

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    public function eof(): bool
    {
        if (is_resource($this->resource)) {
            return feof($this->resource);
        }
        return true;
    }

    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException("Unable to read stream contents.");
        }

        if (false === ($content = stream_get_contents($this->resource))) {
            throw new RuntimeException("Unable to read remainder of the stream.");
        }
        return $content;
    }

    public function getMetadata(?string $key = null)
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $metadata = stream_get_meta_data($this->resource);
        if (!is_null($key)) {
            return array_key_exists($key, $metadata) ? $metadata[$key] : null;
        }
        return $metadata;
    }

    public function getSize(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        if (false !== ($stats = fstat($this->resource))) {
            return $stats['size'];
        }
        return null;
    }

    public function isReadable(): bool
    {
        $mode = $this->getMetadata('mode');
        if (is_string($mode)) {
            return false !== strpbrk($mode, '+r');
        }
        return false;
    }

    public function isSeekable(): bool
    {
        $seekable = $this->getMetadata('seekable');
        return is_bool($seekable) ? $seekable : false;
    }

    public function isWritable(): bool
    {
        $mode = $this->getMetadata('mode');
        if (is_string($mode)) {
            return false !== strpbrk($mode, '+acwx');
        }
        return false;
    }

    public function read(int $length): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException("Cannot read from non-readable stream.");
        }

        if (false === ($stream = fread($this->resource, intval($length)))) {
            throw new RuntimeException("Unable to read from the stream.");
        }
        return $stream;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException("Stream is not seekable.");
        }

        if (0 !== fseek($this->resource, intval($offset), intval($whence))) {
            throw new RuntimeException(sprintf(
                "Unable to seek to stream position '%s' with whence %s.",
                $offset, var_export($whence, true)
            ));
        }
    }

    public function tell(): int
    {
        if (!is_resource($this->resource)) {
            throw new RuntimeException("Stream is not resource-able.");
        }

        if (false === ($position = ftell($this->resource))) {
            throw new RuntimeException("Unable to get the stream pointer position.");
        }
        return $position;
    }

    public function write(string $string): int
    {
        if (!$this->isWritable()) {
            throw new RuntimeException("Cannot write to a non-writable stream.");
        }

        if (false === ($stream = fwrite($this->resource, strval($string)))) {
            throw new RuntimeException("Unable to write to stream.");
        }
        return $stream;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (Throwable) {
            /** ignore... */
        }
        return '';
    }
}