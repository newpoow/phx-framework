<?php declare(strict_types=1);
namespace Phx\Support\Facade;

use Phx\Application;
use Phx\Events\Dispatcher;
use Phx\Events\DispatcherInterface;
use Phx\Package;
use Phx\Support\Facade;

/**
 * Facade for the phx application.
 *
 * @method static mixed get(string $type)
 * @method static Package|null getPackage(string $name)
 */
final class Phx extends Facade
{
    public static function events(): Dispatcher
    {
        return Phx::get(DispatcherInterface::class);
    }

    protected static function getFacadeAccessor(): string
    {
        return Application::class;
    }
}