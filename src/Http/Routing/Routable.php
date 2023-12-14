<?php declare(strict_types=1);
namespace Phx\Http\Routing;

use Psr\Http\Server\RequestHandlerInterface;

/**
 * Defines the methods for creating access routes.
 */
trait Routable
{
    /** @var array<RouteGroup> */
    protected array $groups = [];

    abstract public function map($methods, string $uri, mixed $action): RouteInterface;

    ##+++++++++++++++ PUBLIC METHODS +++++++++++++++##

    public function group(callable $callback): RouteGroup
    {
        $this->groups[] = empty($this->groups) ? new RouteGroup() : new RouteGroup(end($this->groups));
        call_user_func($callback, $this);
        return array_pop($this->groups);
    }

    public function any(string $uri, mixed $action): RouteInterface
    {
        return $this->map('DELETE|GET|HEAD|OPTIONS|PATCH|POST|PUT', $uri, $action);
    }

    public function delete(string $uri, mixed $action): RouteInterface
    {
        return $this->map('DELETE', $uri, $action);
    }

    public function get(string $uri, mixed $action): RouteInterface
    {
        return $this->map('GET|HEAD', $uri, $action);
    }

    public function options(string $uri, mixed $action): RouteInterface
    {
        return $this->map('OPTIONS', $uri, $action);
    }

    public function patch(string $uri, mixed $action): RouteInterface
    {
        return $this->map('PATCH', $uri, $action);
    }

    public function post(string $uri, mixed $action): RouteInterface
    {
        return $this->map('POST', $uri, $action);
    }

    public function put(string $uri, mixed $action): RouteInterface
    {
        return $this->map('PUT', $uri, $action);
    }

    ##+++++++++++++ PROTECTED METHODS ++++++++++++++##

    protected function route(array $methods, string $uri, RequestHandlerInterface $handler): RouteInterface
    {
        if (empty($this->groups)) {
            return new Route($methods, $uri, $handler);
        }

        $group = end($this->groups);
        return $group->createRoute($methods, $uri, $handler);
    }
}