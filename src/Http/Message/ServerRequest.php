<?php declare(strict_types=1);
namespace Phx\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Represents an HTTP Request message on the server.
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    protected array $attributes = [];
    protected array $cookies = [];
    protected array $files = [];
    protected mixed $data;
    protected array $query = [];
    protected array $server = [];

    public function __construct(
        string $method,
        $uri = '',
        ?StreamInterface $body = null,
        array $serverParameters = []
    ) {
        $this->server = $serverParameters;
        $body = $body ?: StreamFactory::createFromFile('php://input', 'rb');

        parent::__construct(strtoupper($method), $uri, $body);
    }

    public function getAttribute(string $name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        return $default;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    public function getParsedBody()
    {
        return $this->data;
    }

    public function getQueryParams(): array
    {
        return $this->query;
    }

    public function getServerParams(): array
    {
        return $this->server;
    }

    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $cloned = clone $this;
        $cloned->attributes[$name] = $value;
        return $cloned;
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $cloned = clone $this;
        $cloned->cookies = $cookies;
        return $cloned;
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        $cloned = clone $this;
        unset($cloned->attributes[$name]);
        return $cloned;
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        if (!is_array($data) && !is_object($data) && !is_null($data)) {
            throw new InvalidArgumentException(
                "The given data is not valid, must be a array, a object or null."
            );
        }

        $cloned = clone $this;
        $cloned->data = $data;
        return $cloned;
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        $cloned = clone $this;
        $cloned->query = $query;
        return $cloned;
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        array_walk_recursive($uploadedFiles, function ($file) {
            if (!$file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException("Invalid uploaded files structure");
            }
        });

        $cloned = clone $this;
        $cloned->files = $uploadedFiles;
        return $cloned;
    }
}