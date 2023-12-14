<?php declare(strict_types=1);
namespace Phx\Http\Events;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Event executed before processing the request.
 */
class BeforeDispatchEvent
{
    public function __construct(
        private ServerRequestInterface $request
    ) {
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
}