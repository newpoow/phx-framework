<?php declare(strict_types=1);
namespace Phx\Support;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Implements base functionality for static "facade" classes.
 */
abstract class Facade
{
    private static ?ContainerInterface $container = null;

    abstract protected static function getFacadeAccessor(): string;

    /** @internal */
    final public static function setContainer(ContainerInterface $container): void
    {
        self::$container ??= $container;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        $instance = static::getFacadeRoot();
        if (!$instance) {
            throw new RuntimeException("A facade root has not been set.");
        }
        return $instance->{$name}(...$arguments);
    }

    protected static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    protected static function resolveFacadeInstance(string $name): mixed
    {
        return self::$container->get($name);
    }
}