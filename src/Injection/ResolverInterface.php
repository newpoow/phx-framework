<?php declare(strict_types=1);
namespace Phx\Injection;

use ReflectionFunctionAbstract;

/**
 * Standardization of a parameter resolver.
 */
interface ResolverInterface
{
    public function resolveParameters(ReflectionFunctionAbstract $reflected, array $primitives = []): array;
}