<?php declare(strict_types=1);
namespace Phx\Http\Support;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Provide knowledge to managing middlewares.
 */
trait MiddlewareTrait
{
    protected array $middlewares = [];

    public function add(MiddlewareInterface $middleware): static
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function import(array $middlewares): static
    {
        foreach ($middlewares as $middleware) $this->add($middleware);
        return $this;
    }
}