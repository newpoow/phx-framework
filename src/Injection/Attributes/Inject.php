<?php declare(strict_types=1);
namespace Phx\Injection\Attributes;

use Attribute;
use Phx\Injection\Injector;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Attribute to inject dependencies in objects.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject
{
    public function __construct(
        public ?string $type = null,
    ) {
    }

    public function __invoke(ReflectionProperty $reflector, object $object): callable
    {
        return function (Injector $injector) use ($reflector, $object) {
            $type = $this->getTypeName($injector, $reflector);
            if ($type) {
                $reflector->setValue($object, $injector->get($type));
            }
            return $object;
        };
    }

    protected function getTypeName(Injector $injector, ReflectionProperty $reflector): ?string
    {
        if ($this->type) {
            return $this->type;
        }

        $type = $reflector->getType();
        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];

        foreach ($types as $type) {
            if ($injector->has($type->getName())) {
                return $type->getName();
            }
        }
        return null;
    }
}