<?php declare(strict_types=1);
namespace Phx\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Represents a HTTP Request message.
 */
class Request extends Message implements RequestInterface
{
    protected ?string $target = null;
    protected UriInterface $uri;

    public function __construct(
        protected string $method,
        string|UriInterface $uri,
        mixed $body = '',
    ) {
        $this->uri = $uri instanceof UriInterface ? $uri : UriFactory::createFromString($uri);
        $this->body = $body instanceof StreamInterface ? $body : StreamFactory::createFromString((string)$body);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getRequestTarget(): string
    {
        if (!is_null($this->target)) {
            return $this->target;
        }

        $origin = '/' . ltrim($this->uri->getPath(), '/');
        if (!empty($query = $this->uri->getQuery())) {
            $origin .= '?' . $query;
        }
        return $origin;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withMethod(string $method): RequestInterface
    {
        if (!preg_match("/^[!#$%&'*+.^_`\|~0-9a-z-]+$/i", $method)) {
            throw new InvalidArgumentException(sprintf(
                "The given method '%s' is not a valid HTTP method.", $method
            ));
        }

        $cloned = clone $this;
        $cloned->method = $method;
        return $cloned;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        if (!preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                "The given request-target is not valid, cannot contain whitespace"
            );
        }

        $cloned = clone $this;
        $cloned->target = $requestTarget;
        return $cloned;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $cloned = clone $this;
        $cloned->uri = $uri;
        if (empty($uri->getHost()) || ($preserveHost && $cloned->hasHeader('host'))) {
            return $cloned;
        }

        $newHost = $uri->getHost();
        if (!is_null($port = $uri->getPort())) {
            $newHost .= ":{$port}";
        }
        return $cloned->withHeader('host', $newHost);
    }
}