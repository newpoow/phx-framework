<?php declare(strict_types=1);
namespace Phx;

use Phx\Injection\Injector;
use Phx\Injection\ServiceProviderInterface;
use Phx\Injection\Support\AggregateServiceTrait;
use Phx\Support\Facade;

/**
 * Framework core package.
 */
final class FrameworkPackage extends Package implements ServiceProviderInterface
{
    use AggregateServiceTrait {
        register as private traitRegister;
    }

    public function __construct(
        private readonly Application $phx
    ) {
        Facade::setContainer($this->phx);
    }

    public function register(Injector $injector): void
    {
        $injector->instance(Application::class, $this->phx);
        $injector->instance(Injector::class, $injector);

        $this->traitRegister($injector);
    }

    public function getProviders(): array
    {
        return [
            Configuration\ServiceProvider::class,
            Events\ServiceProvider::class,
            Http\ServiceProvider::class,
        ];
    }
}