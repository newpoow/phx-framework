<?php declare(strict_types=1);
namespace Phx\Http;

use Phx\Http\Routing\RouteGroup;
use Phx\Http\Routing\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Standardization of an access router.
 */
interface RouterInterface
{
    public function dispatch(ServerRequestInterface $request): ResponseInterface;
    public function group(callable $callback): RouteGroup;

    public function any(string $uri, mixed $action): RouteInterface;
    public function delete(string $uri, mixed $action): RouteInterface;
    public function get(string $uri, mixed $action): RouteInterface;
    public function options(string $uri, mixed $action): RouteInterface;
    public function patch(string $uri, mixed $action): RouteInterface;
    public function post(string $uri, mixed $action): RouteInterface;
    public function put(string $uri, mixed $action): RouteInterface;
}