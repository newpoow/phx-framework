<?php declare(strict_types=1);
namespace Phx;

use Phx\Http\Emitter;
use Phx\Http\Events\AfterDispatchEvent;
use Phx\Http\Events\BeforeDispatchEvent;
use Phx\Http\Exceptions\HttpException;
use Phx\Http\Message\ResponseFactory;
use Phx\Http\Message\ServerRequestFactory;
use Phx\Http\RouterInterface;
use Phx\Http\Server\Pipeline;
use Phx\Http\Support\MiddlewareTrait;
use Phx\Injection\Injector;
use Phx\Support\AwareTraits;
use Phx\Support\Facade\Phx;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Phx application.
 */
class Application extends Kernel
{
    use MiddlewareTrait;
    use AwareTraits\LoadSettingsAwareTrait,
        AwareTraits\BootPackagesAwareTrait,
        AwareTraits\DrawRoutesAwareTrait;

    public static function create(?ContainerInterface $container = null): static
    {
        return new static(null, $container);
    }

    public function __construct(
        protected ?string $rootPath = null,
        ?ContainerInterface $container = null
    ) {
        parent::__construct(new Injector($container));
    }

    public function getAppPath(): string
    {
        return $this->getRootPath().DIRECTORY_SEPARATOR.'app';
    }

    public function getRootPath(): string
    {
        return $this->rootPath ?: dirname($this->getPhxPath(), 3);
    }

    public function getStoragePath(): string
    {
        return $this->getRootPath().DIRECTORY_SEPARATOR.'storage';
    }

    public function run(?ServerRequestInterface $request = null, ?callable $handler = null)
    {
        $this->initialize();

        /** @var BeforeDispatchEvent $event */
        $event = Phx::events()->dispatch(new BeforeDispatchEvent(
            $request ??= ServerRequestFactory::createFromGlobals()
        ));

        $response = $this->sendRequestThroughRouter($event->getRequest());

        /** @var AfterDispatchEvent $event */
        $event = Phx::events()->dispatch(new AfterDispatchEvent($request, $response));

        $handler = $handler ?? [$this, 'sendResponse'];
        return call_user_func($handler, $event->getResponse());
    }

    ##+++++++++++++ PROTECTED METHODS ++++++++++++++##

    protected function sendRequestThroughRouter(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return Pipeline::makeOf(
                $this->middlewares, fn($request) => $this->dispatchToRouter($request)
            )->handle($request);
        } catch (HttpException $exception) {
            $code = $exception->getCode();
        } catch (Throwable $exception) {
            $code = 500;
        }
        return ResponseFactory::createHtmlResponse($code, $exception->getMessage());
    }

    protected function dispatchToRouter(ServerRequestInterface $request): ResponseInterface
    {
        $factory = fn(): RouterInterface => $this->get(RouterInterface::class);
        return $factory()->dispatch($request);
    }

    protected function sendResponse(ResponseInterface $response)
    {
        Emitter::makeOf($response)->send();
    }
}