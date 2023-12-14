<?php declare(strict_types=1);
namespace Phx\Http\Routing;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Represents an access route.
 */
class Route implements RouteInterface
{
    protected RequestHandlerInterface $handler;
    protected array $attributes = [];
    protected array $methods = [];
    protected array $middlewares = [];
    protected array $patterns = [];
    protected string $uri;
    protected ?string $host = null;
    protected ?string $name = null;
    protected ?string $regex = null;

    public function __construct(
        array $methods, string $uri, RequestHandlerInterface $handler
    ) {
        $this->setUri($uri);
        $this->setMethods(...$methods);
        $this->setRequestHandler($handler);
    }

    public function addMiddleware(MiddlewareInterface ...$middlewares): RouteInterface
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    public function addPrefix(string $prefix): RouteInterface
    {
        return $this->setUri(rtrim($prefix, '/') . '/' . trim($this->getUri(), '/'));
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPatterns(): array
    {
        return $this->patterns;
    }

    public function getRequestHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getUriRegex(): ?string
    {
        return $this->regex;
    }

    public function setAttributes(array $attributes): RouteInterface
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function setHost(?string $host): RouteInterface
    {
        $this->host = $host;
        return $this;
    }

    public function setMethods(string ...$methods): RouteInterface
    {
        $methods = array_map('strtoupper', $methods);
        if (in_array('GET', $methods) && !in_array('HEAD', $methods)) {
            $methods[] = 'HEAD';
        }
        $this->methods = array_unique($methods);
        return $this;
    }

    public function setMiddlewares(MiddlewareInterface ...$middlewares): RouteInterface
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    public function setName(string $name): RouteInterface
    {
        $this->name = $name;
        return $this;
    }

    public function setPatterns(array $patterns): RouteInterface
    {
        foreach ($patterns as $alias => $pattern) {
            $this->patterns[$alias] = $pattern;
        }
        return $this;
    }

    public function setRequestHandler(RequestHandlerInterface $handler): RouteInterface
    {
        $this->handler = $handler;
        return $this;
    }

    public function setUri(string $uri): RouteInterface
    {
        $this->uri = '/' . trim($uri, '/');
        return $this;
    }

    public function setUriRegex(string $regex): RouteInterface
    {
        $this->regex = $regex;
        return $this;
    }

    public function withAddedAttributes(array $attributes): RouteInterface
    {
        $cloned = clone $this;
        $cloned->attributes = array_merge($cloned->attributes, array_map(
            fn($attribute) => is_string($attribute) ? trim(rawurldecode($attribute), '\/') : $attribute,
            $attributes
        ));
        return $cloned;
    }
}