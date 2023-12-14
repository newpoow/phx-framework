<?php declare(strict_types=1);
namespace Phx\Support\AwareTraits;

/**
 * Provides knowledge to initialize packages.
 */
trait BootPackagesAwareTrait
{
    protected function bootPackages(): void
    {
        foreach ($this->getPackages(
            fn($package) => method_exists($package, 'boot')
        ) as $object) {
            $this->getInjector()->execute([$object, 'boot']);
        }
    }
}