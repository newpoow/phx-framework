<?php declare(strict_types=1);
namespace Phx\Injection;

/**
 * Provides knowledge to execute container methods.
 */
trait Injectable
{
    abstract public function getInjector(): Injector;

    public function get(string $id): mixed
    {
        return $this->getInjector()->get($id);
    }

    public function has(string $id): bool
    {
        return $this->getInjector()->has($id);
    }
}