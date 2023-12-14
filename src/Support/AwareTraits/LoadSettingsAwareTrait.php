<?php declare(strict_types=1);
namespace Phx\Support\AwareTraits;

use Phx\Configuration\Configurator;

/**
 * Provides knowledge to load configurations.
 */
trait LoadSettingsAwareTrait
{
    protected function loadSettings(): void
    {
        $configurator = $this->getInjector()->get(Configurator::class);

        foreach ($this->getPackages(
            fn($package) => method_exists($package, 'configure')
        ) as $object) {
            $object->configure($configurator);
        }
    }
}