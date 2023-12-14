<?php declare(strict_types=1);
namespace Phx\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Represents a HTTP Response message.
 */
class Response extends Message implements ResponseInterface
{
    public function __construct(
        protected int $statusCode = 200,
        string|StreamInterface $body = '',
        array $headers = [],
        protected ?string $phrase = null,
    ) {
        $this->body = $body instanceof StreamInterface ? $body : StreamFactory::createFromString($body);

        $this->setStatusCode($statusCode);
        $this->body = $body instanceof StreamInterface ? $body : new Stream($body);
        $this->headers = $this->normalizeHeaders($headers);
    }

    public function getReasonPhrase(): string
    {
        return $this->phrase;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $cloned = clone $this;
        $cloned->setStatusCode($code);
        if (is_string($reasonPhrase) && !empty($reasonPhrase)) {
            $cloned->phrase = $reasonPhrase;
        }
        return $cloned;
    }

    protected function setStatusCode(int $code): self
    {
        if (!($code >= 100 && $code <= 599)) {
            throw new InvalidArgumentException(
                "The given status-code is not valid, must be an integer between 100 and 599, inclusive."
            );
        }

        $this->statusCode = $code;
        $this->phrase = HttpStatus::getPhrase($code);
        return $this;
    }
}