<?php declare(strict_types=1);
namespace Phx\Http\Routing;

use Closure;
use JsonSerializable;
use Phx\Http\Exceptions\RouterException;
use Phx\Http\Message\ResponseFactory;
use Phx\Injection\Injector;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Handler of the action defined in the access route.
 */
class RouteHandler implements RequestHandlerInterface
{
    protected mixed $action;

    public function __construct(
        protected readonly Injector $injector
    ) {
    }

    public function makeOf(mixed $action): RequestHandlerInterface
    {
        if ($action instanceof RequestHandlerInterface) {
            return $action;
        }

        $cloned = clone $this;
        return $cloned->setAction($action);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        ob_start();
        $level = ob_get_level();

        try {
            $response = $this->injector->execute($this->getAction(), array_merge(
                [ServerRequestInterface::class => $request], $request->getAttributes()
            ));

            $response = $this->prepareResponse($response);

            $content = '';
            while (ob_get_level() >= $level) {
                $content = ob_get_clean().$content;
            }

            $response->getBody()->write($content);
            return $response;
        } finally {
            while (ob_get_level() >= $level) {
                ob_end_clean();
            }
        }
    }

    public function getAction(): callable
    {
        $action = $this->action;
        if (is_array($action)) {
            $controller = $action['controller'];
            $method = $action['method'];

            if (is_string($controller)) {
                if (!$this->injector->has($controller)) {
                    throw new RouterException(sprintf(
                        "The controller class '%s' has not been defined.", trim($controller, '\\')
                    ));
                }
                $controller = $this->injector->make($controller);
            }

            if (!method_exists($controller, $method)) {
                throw new RouterException(sprintf(
                    "The controller class '%s' does not have a '%s' method.",
                    get_class($controller), $method
                ));
            }
            return array($controller, $method);
        }
        return $action instanceof Closure ? $action->bindTo($this->injector) : $action;
    }

    public function setAction(mixed $action): static
    {
        if (!is_callable($action) && is_string($action)) {
            $action = explode('@', str_replace(array(':'), '@', $action), 2);
            if (count($action) == 1) {
                $action = array_merge($action, array('__invoke'));
            }
        }

        if ((!is_callable($action) && !is_array($action)) || (is_array($action) && count($action) < 2)) {
            throw new RouterException(
                "The action format entered is not valid."
            );
        }

        if (is_array($action)) {
            list($controller, $method) = $action;
            $action = compact('controller', 'method');
        }
        $this->action = $action;
        return $this;
    }

    protected function prepareResponse($content): ResponseInterface
    {
        if ($content instanceof ResponseInterface) {
            return $content;
        }

        if (is_array($content) || $content instanceof JsonSerializable) {
            return ResponseFactory::createJsonResponse(200, $content);
        }
        return ResponseFactory::createHtmlResponse(200, (string)$content);
    }
}