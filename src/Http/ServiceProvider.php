<?php declare(strict_types=1);
namespace Phx\Http;

use Phx\Http\Message as Phx;
use Phx\Http\Routing\Router;
use Phx\Injection\Injector;
use Phx\Injection\ServiceProviderInterface;
use Psr\Http\Message as Psr;

/**
 * Register services for handle http requests.
 */
class ServiceProvider implements ServiceProviderInterface
{
    public function register(Injector $injector): void
    {
        MiddlewareFactory::setInjector($injector);

        $this->registerPSR($injector);
        $this->registerRouter($injector);
    }

    protected function registerPSR(Injector $injector)
    {
        $injector->bind(Psr\RequestFactoryInterface::class, Phx\RequestFactory::class);
        $injector->bind(Psr\ResponseFactoryInterface::class, Phx\ResponseFactory::class);
        $injector->bind(Psr\ServerRequestFactoryInterface::class, Phx\ServerRequestFactory::class);
        $injector->bind(Psr\StreamFactoryInterface::class, Phx\StreamFactory::class);
        $injector->bind(Psr\UploadedFileFactoryInterface::class, Phx\UploadedFileFactory::class);
        $injector->bind(Psr\UriFactoryInterface::class, Phx\UriFactory::class);
    }

    protected function registerRouter(Injector $injector): void
    {
        $injector->alias('router', RouterInterface::class);
        $injector->singleton(RouterInterface::class, Router::class);
    }
}