<?php declare(strict_types=1);
namespace Phx\Http\Routing;

use Phx\Http\Exceptions\MethodNotAllowedHttpException;
use Phx\Http\Exceptions\NotFoundHttpException;
use Phx\Http\RouterInterface;
use Phx\Http\Server\Pipeline;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Router for application access.
 */
class Router implements RouterInterface
{
    use Routable;

    protected RouteCompiler $compiler;
    protected array $hosts = [];
    protected array $routes = [];
    protected array $patterns = [
        '**' => '.+?', ## all
        '*' => '[^/\.]++', ## / between /
        'i' => '[0-9]++', ## int
        'c' => '[A-Za-z]++', ## char
        'a' => '[0-9A-Za-z]++', ## alpha
        'h' => '[0-9A-Fa-f]++', ## hex
        's' => '[0-9A-Za-z-_]++', ## slug
        'y' => '[12][0-9]{3}', ## year
        'm' => '[1-9]|0[1-9]|1[012]', ## month
        'd' => '[1-9]|0[1-9]|[12][0-9]|3[01]' ## day
    ];

    ##+++++++++++++++ PUBLIC METHODS +++++++++++++++##

    public function __construct(
        protected readonly RouteHandler $routeHandler,
        ?RouteCompiler $compiler = null
    ) {
        $this->compiler = $compiler ?: new RouteCompiler();
    }

    public function addHost(string $alias, string ...$hostnames): static
    {
        $this->hosts[$alias] = $hostnames;
        return $this;
    }

    public function addPatterns(array $patterns): static
    {
        foreach ($patterns as $alias => $pattern) {
            $this->patterns[$alias] = $pattern;
        }
        return $this;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $route = $this->findRoute($request);
        foreach ($route->getAttributes() as $name => $parameter) {
            $request = $request->withAttribute($name, $parameter);
        }

        $request = $request->withAttribute(RouteInterface::class, $route);
        return Pipeline::makeOf(
            $route->getMiddlewares(), fn($request) => $route->getRequestHandler()->handle($request)
        )->handle($request);
    }

    public function getRoutes(?callable $filter = null): array
    {
        if (is_null($filter)) {
            return $this->routes;
        }
        return array_filter($this->routes, $filter, ARRAY_FILTER_USE_BOTH);
    }

    public function getRoutesByHostname(string $hostname): array
    {
        $hostname = $this->resolveHostname($hostname);
        return $this->getRoutes(function (RouteInterface $route) use ($hostname) {
            $host = $route->getHost();
            return is_null($host) || $host === $hostname;
        });
    }

    public function map($methods, string $uri, mixed $action): RouteInterface
    {
        if (is_string($methods)) {
            $methods = explode('|', str_replace(array(','), '|', $methods));
        }

        $route = $this->route($methods, $uri, $this->routeHandler->makeOf($action));
        foreach ($route->getMethods() as $method) {
            $this->routes[$method.spl_object_hash($route)] = $route;
        }
        return $route;
    }

    public function resolveHostname(string $hostname): string
    {
        foreach ($this->hosts as $alias => $hostnames) {
            foreach ($hostnames as $host) {
                if ($hostname === $host) return $alias;
            }
        }
        return $hostname;
    }

    ##+++++++++++++ PROTECTED METHODS ++++++++++++++##

    protected function findRoute(ServerRequestInterface $request): RouteInterface
    {
        $currentMethod = $request->getMethod();
        $currentPath = rawurldecode($request->getUri()->getPath());

        $allowed = [];
        /** @var array<RouteInterface> $routes */
        $routes = $this->sortRoutes($this->getRoutesByHostname($request->getUri()->getHost()), $request);
        foreach ($routes as $route) {
            if (!preg_match($this->routeToRegex($route), $currentPath, $attributes)) {
                continue;
            }

            $routeMethods = $route->getMethods();
            $allowed = array_unique(array_merge($allowed, $routeMethods));
            if (!in_array($currentMethod, $routeMethods)) {
                continue;
            }

            return $route->withAddedAttributes(array_filter(
                $attributes, fn($value, $name) => !empty($value) && !is_int($name), ARRAY_FILTER_USE_BOTH
            ));
        }

        if (!empty($allowed)) {
            throw new MethodNotAllowedHttpException($allowed);
        }
        throw new NotFoundHttpException($currentPath);
    }

    protected function routeToRegex(RouteInterface $route): string
    {
        $regex = $route->getUriRegex();
        if (is_null($regex)) {
            $regex = $this->compiler->compile($route->getUri(), array_merge($this->patterns, $route->getPatterns()));
            $route->setUriRegex($regex);
        }
        return $regex;
    }

    protected function sortRoutes(array $routes, ServerRequestInterface $request): array
    {
        $uniqueRoutes = [];
        foreach ($routes as $route) {
            foreach ($route->getMethods() as $method) $uniqueRoutes[$method.$route->getUri()] = $route;
        }

        ksort($uniqueRoutes);
        $sorted = array_filter(
            $uniqueRoutes,
            fn(string $key) => preg_match("/^{$request->getMethod()}/", $key),
            ARRAY_FILTER_USE_KEY
        );
        return array_merge($sorted, array_diff_key($uniqueRoutes, $sorted));
    }
}