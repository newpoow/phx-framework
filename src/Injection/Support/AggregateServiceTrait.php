<?php declare(strict_types=1);
namespace Phx\Injection\Support;

use Phx\Injection\Injector;
use Phx\Injection\ServiceProviderInterface;

/**
 * Provide knowledge to load services into the injector.
 */
trait AggregateServiceTrait
{
    abstract public function getProviders(): array;

    public function register(Injector $injector): void
    {
        $providers = array_map(
            fn($provider) => is_string($provider) ? $injector->get($provider) : $provider, $this->getProviders()
        );

        foreach ($providers as $provider) {
            /** @var ServiceProviderInterface $provider */
            $provider->register($injector);
        }
    }
}