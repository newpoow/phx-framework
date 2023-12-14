<?php declare(strict_types=1);
namespace Phx\Http\Routing;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Standardization of an access route.
 */
interface RouteInterface
{
    public function addMiddleware(MiddlewareInterface ...$middlewares): RouteInterface;
    public function addPrefix(string $prefix): RouteInterface;

    public function getAttributes(): array;
    public function getHost(): ?string;
    public function getMethods(): array;
    public function getMiddlewares(): array;
    public function getName(): ?string;
    public function getPatterns(): array;
    public function getRequestHandler(): RequestHandlerInterface;
    public function getUri(): string;
    public function getUriRegex(): ?string;

    public function setAttributes(array $attributes): RouteInterface;
    public function setHost(?string $host): RouteInterface;
    public function setMethods(string ...$methods): RouteInterface;
    public function setMiddlewares(MiddlewareInterface ...$middlewares): RouteInterface;
    public function setName(string $name): RouteInterface;
    public function setPatterns(array $patterns): RouteInterface;
    public function setRequestHandler(RequestHandlerInterface $handler): RouteInterface;
    public function setUri(string $uri): RouteInterface;
    public function setUriRegex(string $regex): RouteInterface;

    public function withAddedAttributes(array $attributes): RouteInterface;
}