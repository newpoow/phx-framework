<?php declare(strict_types=1);
namespace Phx\Injection;

use Closure;
use Phx\Injection\Exceptions\ContainerException;
use Phx\Injection\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Dependency Injector.
 */
final class Injector
{
    private ResolverInterface $resolver;
    private array $aliases = [];
    private array $definitions = [];
    private array $instances = [];
    private array $resolving = [];

    public function __construct(
        private ?ContainerInterface $container = null,
        ?ResolverInterface $resolver = null
    ) {
        $this->resolver = $resolver ?? new Resolver($this);
    }

    public function alias(string $alias, string $type): Injector
    {
        if ($alias === $type) {
            throw new ContainerException(sprintf(
                "The alias '%s' is aliased to itself.", $alias
            ));
        }
        $this->aliases[$alias] = $type;
        return $this;
    }

    public function bind(string $type, $concrete = null, bool $shared = false): Injector
    {
        $this->removeAlias($type);

        $concrete = is_null($concrete) ? $type : $concrete;
        if (!$concrete instanceof Closure) {
            $concrete = function () use ($concrete) {
                if (is_string($concrete)) {
                    return $this->build($concrete);
                }
                return $concrete;
            };
        }

        $this->definitions[$type] = compact('concrete', 'shared');
        return $this;
    }

    public function execute(callable $fn, array $parameters = []): mixed
    {
        try {
            if (is_array($fn)) {
                list($class, $method) = $fn;
                $reflected = new ReflectionMethod($class, $method);
            } else if (is_object($fn) && !$fn instanceof Closure) {
                $reflected = new ReflectionMethod($fn, '__invoke');
            } else {
                $reflected = new ReflectionFunction($fn);
            }

            return call_user_func_array($fn, $this->resolver->resolveParameters(
                $reflected, $parameters
            ));
        } catch (ReflectionException $exception) {
            throw new ContainerException(
                $exception->getMessage(), $exception->getCode(), $exception->getPrevious()
            );
        }
    }

    public function get(string $type): mixed
    {
        try {
            if (is_null($this->container)) {
                throw new EntryNotFoundException(sprintf(
                    "No entry was found for '%s' identifier in the container.", $type
                ));
            }

            return $this->container->get($type);
        } catch (ContainerExceptionInterface $exception) {
            if ($exception instanceof NotFoundExceptionInterface && $this->isResolvable($type)) {
                return $this->make($type);
            }
            throw new ContainerException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function getTypeFromAlias(string $alias): string
    {
        $aliases = array();
        while (isset($this->aliases[$alias])) {
            $aliases[] = $alias;
            if (in_array($alias = $this->aliases[$alias], $aliases)) {
                throw new ContainerException(sprintf(
                    "The alias '%s' contains a circular entry.", $alias
                ));
            }
        }
        return $alias;
    }

    public function has(string $type): bool
    {
        return ($this->container && $this->container->has($type)) || $this->isResolvable($type);
    }

    public function instance(string $type, $instance): Injector
    {
        $this->removeAlias($type);
        $this->instances[$type] = $instance;
        return $this;
    }

    public function make(string $type, array $parameters = []): object
    {
        $type = $this->getTypeFromAlias($type);
        if (isset($this->instances[$type])) {
            return $this->instances[$type];
        }

        if (isset($this->definitions[$type])) {
            $def = $this->definitions[$type];

            if ($def['shared'] === true) {
                return $this->instances[$type] = $this->execute($def['concrete'], $parameters);
            }
            return $this->execute($def['concrete'], $parameters);
        }
        return $this->build($type, $parameters);
    }

    public function removeAlias(string $alias): Injector
    {
        if (isset($this->aliases[$alias])) {
            unset($this->aliases[$alias]);
        }
        return $this;
    }

    public function setContainer(?ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function singleton(string $type, $concrete = null): Injector
    {
        return $this->bind($type, $concrete, true);
    }

    ##+++++++++++++ PRIVATE METHODS ++++++++++++++++##

    private function build(string $abstract, array $parameters = []): object
    {
        if (isset($this->resolving[$abstract])) {
            throw new ContainerException(sprintf(
                "Circular dependency detected while trying to resolve entry '%s'.", $abstract
            ));
        }
        $this->resolving[$abstract] = true;

        try {
            return $this->createObject($abstract, $parameters);
        } catch (ReflectionException $exception) {
            throw new ContainerException(
                $exception->getMessage(), $exception->getCode(), $exception->getPrevious()
            );
        } finally {
            unset($this->resolving[$abstract]);
        }
    }

    /** @throws ReflectionException */
    private function createObject(string $type, array $parameters = []): object
    {
        $reflected = new ReflectionClass($type);
        if (!$reflected->isInstantiable()) {
            throw new ContainerException(sprintf(
                "Target '%s' is not instantiable.", $type
            ));
        }

        $constructor = $reflected->getConstructor();
        if (is_null($constructor)) {
            return $reflected->newInstance();
        }
        return $reflected->newInstanceArgs($this->resolver->resolveParameters($constructor, $parameters));
    }

    private function isResolvable(string $type): bool
    {
        $type = $this->getTypeFromAlias($type);
        if (isset($this->instances[$type]) || isset($this->definitions[$type])) {
            return true;
        }
        return $type != 'Closure' && class_exists($type);
    }
}