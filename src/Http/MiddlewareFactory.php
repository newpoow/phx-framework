<?php declare(strict_types=1);
namespace Phx\Http;

use Phx\Http\Server\LazyMiddleware;
use Phx\Injection\Injector;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Middleware factory.
 */
class MiddlewareFactory
{
    private static Injector $injector;

    public static function setInjector(Injector $injector): void
    {
        static::$injector = $injector;
    }

    public static function makeOf(string $middleware): MiddlewareInterface
    {
        return new LazyMiddleware(static::$injector, $middleware);
    }
}