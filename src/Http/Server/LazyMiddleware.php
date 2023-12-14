<?php declare(strict_types=1);
namespace Phx\Http\Server;

use InvalidArgumentException;
use Phx\Injection\Injector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware for execution defer.
 */
class LazyMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Injector $injector, protected string $middleware
    ) {
        if (false === $this->injector->has($this->middleware)) {
            throw new InvalidArgumentException(sprintf('Container is missing middleware "%s"', $middleware));
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->resolve()->process($request, $handler);
    }

    private function resolve(): MiddlewareInterface
    {
        return $this->injector->get($this->middleware);
    }
}