<?php declare(strict_types=1);
namespace Phx;

use Closure;
use InvalidArgumentException;
use Phx\Injection\Injectable;
use Phx\Injection\Injector;
use Phx\Injection\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Core of the application.
 */
abstract class Kernel implements ContainerInterface
{
    use Injectable;

    protected array $packages = [];

    ##+++++++++++++++ PUBLIC METHODS +++++++++++++++##

    public function __construct(
        private readonly Injector $injector
    ) {
        $this->register(FrameworkPackage::class);
    }

    final public function getInjector(): Injector
    {
        return $this->injector;
    }

    public function getPackage(string $type): ?Package
    {
        return $this->getPackages()[$type] ?? null;
    }

    public function getPackages(?Closure $filter = null): array
    {
        return is_null($filter)
            ? $this->packages
            : array_filter($this->packages, $filter, ARRAY_FILTER_USE_BOTH);
    }

    final public function getPhxPath(): string
    {
        return realpath(dirname(__FILE__, 2));
    }

    public function register(string|Package $package, bool $replace = false): static
    {
        if (is_string($package)) $package = $this->makePackage($package);

        $packageType = get_class($package);
        if (!array_key_exists($packageType, $this->packages) || $replace) {
            $this->registerPackagesDependencies($packageType, $package, $replace);

            if ($package instanceof ServiceProviderInterface) $this->registerServices($package);
            $this->packages[$packageType] = $package;
        }
        return $this;
    }

    public function registerServices(ServiceProviderInterface $serviceProvider): static
    {
        $serviceProvider->register($this->getInjector());
        return $this;
    }

    ##+++++++++++++ PROTECTED METHODS ++++++++++++++##

    final protected function getAwareTraits(): array
    {
        $traits = array();
        $current = $this;
        do {
            $uses = array_filter(class_uses($current), fn($use) => $this->getTraitPosition($use));
            $traits = array_merge(
                array_diff($uses, $traits), $traits
            );
        } while ($current = get_parent_class($current));
        return $traits;
    }

    final protected function getInitMethodFromTrait(string $trait): ?string
    {
        if (($position = strrpos($trait, '\\')) !== false) {
            $trait = substr($trait, $position + 1);
        }

        if (($length = $this->getTraitPosition($trait)) !== false) {
            return substr($trait, 0, $length);
        }
        return null;
    }

    protected function initialize(): static
    {
        foreach ($this->getAwareTraits() as $aware) {
            $method = $this->getInitMethodFromTrait($aware);
            if ($method && method_exists($this, ($method = lcfirst($method)))) {
                call_user_func([$this, $method]);
            }
        }
        return $this;
    }

    protected function makePackage(string $className): Package
    {
        $package = new $className($this);
        if (!$package instanceof Package) {
            throw new InvalidArgumentException(sprintf(
                "The given package %s is not a valid %s class", get_class($package), Package::class
            ));
        }
        return $package;
    }

    ##+++++++++++++ PRIVATE METHODS ++++++++++++++++##

    private function registerPackagesDependencies(string $packageType, Package $package, bool $replace)
    {
        if (method_exists($package, 'getPackages')) {
            try {
                $this->packages[$packageType] = $package;
                foreach ($package->getPackages() as $dependency) $this->register($dependency, $replace);
            } finally {
                unset($this->packages[$packageType]);
            }
        }
    }

    private function getTraitPosition(string $trait): bool|int
    {
        return strrpos($trait, 'AwareTrait');
    }
}