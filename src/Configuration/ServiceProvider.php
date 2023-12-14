<?php declare(strict_types=1);
namespace Phx\Configuration;

use Phx\Configuration\Parsers\PhpParser;
use Phx\Injection\Injector;
use Phx\Injection\ServiceProviderInterface;

/**
 * Register services for loading configuration.
 */
class ServiceProvider implements ServiceProviderInterface
{
    public function register(Injector $injector): void
    {
        $injector->alias('config', Configurator::class);
        $injector->singleton(Configurator::class, function () {
            return (new Configurator('.'))
                ->addParser(new PhpParser(), 'php');
        });
    }
}