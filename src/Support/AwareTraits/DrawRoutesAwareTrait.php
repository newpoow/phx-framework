<?php declare(strict_types=1);
namespace Phx\Support\AwareTraits;

use Phx\Http\RouterInterface;

/**
 * Provides knowledge to define the routes.
 */
trait DrawRoutesAwareTrait
{
    protected function drawRoutes(): void
    {
        $router = $this->getInjector()->get(RouterInterface::class);

        foreach ($this->getPackages(
            fn($package) => method_exists($package, 'drawRoutes')
        ) as $object) {
            $object->drawRoutes($router);
        }
    }
}