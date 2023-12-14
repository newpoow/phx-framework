<?php declare(strict_types=1);
namespace Phx\Http\Routing;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Route group with common attributes.
 */
class RouteGroup
{
    protected array $routes = [];

    ##+++++++++++++++ PUBLIC METHODS +++++++++++++++##

    public function __construct(
        protected readonly ?RouteGroup $parent = null
    ) {
    }

    public function addMiddleware(MiddlewareInterface ...$middlewares): static
    {
        return $this->mapRoutes(fn(RouteInterface $route) => $route->addMiddleware(...$middlewares));
    }

    public function createRoute(array $methods, string $uri, RequestHandlerInterface $handler): RouteInterface
    {
        $this->routes[] = $route = new Route($methods, $uri, $handler);
        if ($this->parent) {
            $this->parent->routes[] = $route;
        }
        return $route;
    }

    public function host(string $host): static
    {
        return $this->mapRoutes(fn(RouteInterface $route) => $route->setHost($host));
    }

    public function prefix(string $prefix): static
    {
        return $this->mapRoutes(fn(RouteInterface $route) => $route->addPrefix($prefix));
    }

    public function prependMiddleware(MiddlewareInterface ...$middlewares): static
    {
        return $this->mapRoutes(
            fn(RouteInterface $route) => $route->setMiddlewares(...array_merge($middlewares, $route->getMiddlewares()))
        );
    }

    ##+++++++++++++ PROTECTED METHODS ++++++++++++++##

    protected function mapRoutes(callable $mapper): static
    {
        array_map($mapper, $this->routes);
        return $this;
    }
}