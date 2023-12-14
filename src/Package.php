<?php declare(strict_types=1);
namespace Phx;

use Phx\Support\Facade;
use ReflectionClass;

/**
 * Implements base functionality for packages.
 */
abstract class Package
{
    final public static function me(): ?static
    {
        return Facade\Phx::getPackage(get_called_class());
    }

    public function getFileName(): string
    {
        return (new ReflectionClass(static::class))->getFileName();
    }

    public function getNamespace(): string
    {
        return (new ReflectionClass(static::class))->getNamespaceName();
    }

    public function getRootPath(): string
    {
        return dirname($this->getFileName(), 2);
    }
}