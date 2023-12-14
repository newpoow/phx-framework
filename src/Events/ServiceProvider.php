<?php declare(strict_types=1);
namespace Phx\Events;

use Phx\Injection\Injector;
use Phx\Injection\ServiceProviderInterface;

/**
 * Register services for dispatching events.
 */
class ServiceProvider implements ServiceProviderInterface
{
    public function register(Injector $injector): void
    {
        $injector->alias('events', DispatcherInterface::class);
        $injector->singleton(DispatcherInterface::class, Dispatcher::class);
    }
}