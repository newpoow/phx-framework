<?php declare(strict_types=1);
namespace Phx\Injection;

use Closure;
use LogicException;
use Phx\Injection\Exceptions\ContainerException;
use Phx\Injection\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionObject;
use ReflectionParameter;
use ReflectionUnionType;
use Reflector;

/**
 * Dependency Injector.
 */
final class Injector
{
    private array $aliases = [];
    private array $definitions = [];
    private array $instances = [];
    private array $resolving = [];

    ##+++++++++++++++ PUBLIC METHODS +++++++++++++++##

    public function __construct(
        private ?ContainerInterface $container = null
    ) {
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

            return call_user_func_array($fn, $this->resolveParameters(
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
            $container = $this->getContainer();
            if (is_null($container)) {
                throw new EntryNotFoundException(sprintf(
                    "No entry was found for '%s' identifier in the container.", $type
                ));
            }

            return $container->get($type);
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
        return $this->getContainer()?->has($type) || $this->isResolvable($type);
    }

    public function instance(string $type, $instance): Injector
    {
        $this->removeAlias($type);
        $this->instances[$type] = $instance;
        return $this;
    }

    public function make(string $type, array $parameters = [], bool $newInstance = false): object
    {
        $type = $this->getTypeFromAlias($type);
        if (isset($this->instances[$type]) && !$newInstance) {
            return $this->instances[$type];
        }

        if (isset($this->definitions[$type])) {
            $def = $this->definitions[$type];

            if ($def['shared'] === true && !$newInstance) {
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

    public function resolveParameters(ReflectionFunctionAbstract $reflected, array $primitives = []): array
    {
        $parameters = array();
        foreach ($reflected->getParameters() as $index => $parameter) {
            $value = $this->getParameterValue($parameter, $primitives);
            if ($parameter->isVariadic()) {
                return array_merge($parameters, array_values((array)$value));
            }

            if (!is_null($value) || $parameter->isOptional()) {
                $parameters[$index] = $value;
                continue;
            }

            $where = $reflected->getName();
            if ($class = $parameter->getDeclaringClass()) {
                $where = "{$class->getName()}::$where()";
            }

            throw new ContainerException(sprintf(
                "Identifier '$%s' cannot be resolved in '%s'.", $parameter->getName(), $where
            ));
        }
        return $parameters;
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

    private function build(string $type, array $parameters = []): object
    {
        if (isset($this->resolving[$type])) {
            throw new ContainerException(sprintf(
                "Circular dependency detected while trying to resolve entry '%s'.", $type
            ));
        }
        $this->resolving[$type] = true;

        try {
            return $this->decorateObject($this->createObject($type, $parameters));
        } catch (ReflectionException $exception) {
            throw new ContainerException(
                $exception->getMessage(), $exception->getCode(), $exception->getPrevious()
            );
        } finally {
            unset($this->resolving[$type]);
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
        return $reflected->newInstanceArgs($this->resolveParameters($constructor, $parameters));
    }

    private function decorateObject(object $object): object
    {
        $reflected = new ReflectionObject($this->resolveProperties($object));
        foreach ($reflected->getAttributes() as $attribute) {
            $object = $this->execute($this->runAttribute($attribute, $reflected, $object));
        }
        return $object;
    }

    private function getFromPrimitives(string $nameParameter, array $primitives)
    {
        if (array_key_exists($nameParameter, $primitives)) {
            return $primitives[$nameParameter];
        }
        return null;
    }

    private function getParameterValue(ReflectionParameter $parameter, array $primitives)
    {
        $name = $parameter->getName();
        if (($value = $this->getFromPrimitives($name, $primitives)) !== null) {
            return $value;
        }

        $class = $parameter->getType();
        if ($class && ($class instanceof ReflectionUnionType || !$class->isBuiltin())) {
            $types = array_map(
                fn($type) => $type->getName(), $class instanceof ReflectionUnionType
                    ? $class->getTypes() : [$class]
            );

            foreach ($types as $type) {
                if (($value = $this->getFromPrimitives($type, $primitives)) !== null) {
                    return $value;
                }

                if ($this->has($type)) {
                    return $this->get($type);
                }
            }
        }
        return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
    }

    private function isAttribute(string $type): bool
    {
        try {
            $ref = new ReflectionClass($type);
            foreach ($ref->getAttributes() as $attribute) {
                if ($attribute->getName() === 'Attribute') return true;
            }
        } catch (ReflectionException) {}

        return false;
    }

    private function isResolvable(string $type): bool
    {
        $type = $this->getTypeFromAlias($type);
        if (isset($this->instances[$type]) || isset($this->definitions[$type])) {
            return true;
        }
        return $type != 'Closure' && class_exists($type) && !$this->isAttribute($type);
    }

    private function resolveProperties(object $object): object
    {
        $reflector = new ReflectionObject($object);
        foreach ($reflector->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                $object = $this->execute($this->runAttribute($attribute, $property, $object));
            }
        }
        return $object;
    }

    private function runAttribute(
        ReflectionAttribute $attribute, Reflector $reflector, object $object
    ): callable {
        $instance = $attribute->newInstance();
        if (!is_callable($instance)) {
            throw new LogicException(sprintf(
                "Attribute '%s'  is not invokable.", get_class($instance)
            ));
        }
        return call_user_func($instance, $reflector, $object);
    }
}