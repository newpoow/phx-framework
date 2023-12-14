<?php declare(strict_types=1);
namespace Phx\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Abstraction of the hypertext transfer protocol message.
 */
abstract class Message implements MessageInterface
{
    protected StreamInterface $body;
    protected array $headers = [];
    protected string $version = '1.1';

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function getHeader(string $name): array
    {
        $header = $this->normalizeHeaderName($name);
        if (!array_key_exists($header, $this->headers)) {
            return array();
        }
        return $this->headers[$header];
    }

    public function getHeaderLine(string $name): string
    {
        if (empty($headers = $this->getHeader($name))) {
            return '';
        }
        return implode(', ', $headers);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    public function hasHeader(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->headers);
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $header = $this->normalizeHeaderName($name);
        $cloned = clone $this;
        $cloned->headers[$header] = array_merge($this->headers[$header], $this->normalizeHeaderValues(
            is_array($value) ? $value : array($value)
        ));
        return $cloned;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $cloned = clone $this;
        $cloned->body = $body;
        return $cloned;
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $cloned = clone $this;
        $cloned->headers[$this->normalizeHeaderName($name)] = $this->normalizeHeaderValues(
            is_array($value) ? $value : array($value)
        );
        return $cloned;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        if (!preg_match('/^\d(?:\.\d)?$/', $version)) {
            throw new InvalidArgumentException(sprintf(
                "The given protocol version '%s' is not valid; use the format: <major>.<minor> numbering scheme",
                $version
            ));
        }

        $cloned = clone $this;
        $cloned->version = $version;
        return $cloned;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $cloned = clone $this;
        unset($cloned->headers[$this->normalizeHeaderName($name)]);
        return $cloned;
    }

    ##+++++++++++++ PROTECTED METHODS ++++++++++++++##

    protected function normalizeHeaderName(string $header): string
    {
        if (!preg_match("@^[a-zA-Z0-9'`#$%&*+.^_|~!-]+$@", $header)) {
            throw new InvalidArgumentException(sprintf(
                "The given header name '%s' is not valid; must be an RFC 7230 compatible string.",
                $header
            ));
        }
        return strtolower($header);
    }

    protected function normalizeHeaders(array $headers): array
    {
        $normalized = array();
        foreach ($headers as $header => $value) {
            $normalized[$this->normalizeHeaderName($header)] = $this->normalizeHeaderValues(
                is_array($value) ? $value : array($value)
            );
        }
        return $normalized;
    }

    protected function normalizeHeaderValues(array $headers): array
    {
        if (empty($headers)) {
            throw new InvalidArgumentException(
                "Header values must be an array of strings, cannot be an empty array."
            );
        }

        return array_map(function (string $header) {
            if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $header)
                || preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $header)) {
                throw new InvalidArgumentException(sprintf(
                    "The given header value '%s' is not valid", $header
                ));
            }
            return $header;
        }, array_values($headers));
    }
}