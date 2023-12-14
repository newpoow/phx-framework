<?php declare(strict_types=1);
namespace Phx\Injection;

use Phx\Injection\Exceptions\ContainerException;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Parameter resolver for functions/methods.
 */
class Resolver implements ResolverInterface
{
    public function __construct(
        protected readonly Injector $injector
    ) {
    }

    public function resolveParameters(ReflectionFunctionAbstract $reflected, array $primitives = []): array
    {
        $parameters = array();
        foreach ($reflected->getParameters() as $index => $parameter) {
            $value = $this->getParameterValue($parameter, $primitives);
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

    protected function getFromPrimitives(string $nameParameter, array $primitives)
    {
        if (array_key_exists($nameParameter, $primitives)) {
            return $primitives[$nameParameter];
        }
        return null;
    }

    protected function getParameterValue(ReflectionParameter $parameter, array $primitives): mixed
    {
        $name = $parameter->getName();
        if (($value = $this->getFromPrimitives($name, $primitives)) !== null) {
            return $value;
        }

        $class = $parameter->getType();
        if ($class instanceof ReflectionNamedType) {
            $type = $class->getName();
            if (($value = $this->getFromPrimitives($type, $primitives)) !== null) {
                return $value;
            }

            if ($this->injector->has($type)) {
                return $this->injector->get($type);
            }
        }
        return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
    }
}