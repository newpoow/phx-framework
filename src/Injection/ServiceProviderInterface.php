<?php declare(strict_types=1);
namespace Phx\Injection;

/**
 * Standardization of a service provider.
 */
interface ServiceProviderInterface
{
    public function register(Injector $injector): void;
}