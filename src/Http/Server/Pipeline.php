<?php declare(strict_types=1);
namespace Phx\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplQueue;

/**
 * Process a server request and produce a response.
 */
final class Pipeline implements RequestHandlerInterface
{
    protected SplQueue $queue;
    protected $fallback;

    public static function makeOf(iterable $middlewares, callable $fallback): Pipeline
    {
        $pipeline = new Pipeline($fallback);
        foreach ($middlewares as $middleware) $pipeline->add($middleware);
        return $pipeline;
    }

    public function __construct(callable $fallback)
    {
        $this->queue = new SplQueue();
        $this->fallback = $fallback;
    }

    public function add(MiddlewareInterface $middleware): Pipeline
    {
        $this->queue->enqueue($middleware);
        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->queue->isEmpty()) {
            return call_user_func($this->fallback, $request);
        }

        $middleware = $this->queue->dequeue();
        return $middleware->process($request, $this);
    }
}