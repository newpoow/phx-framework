<?php declare(strict_types=1);
namespace Phx\Http\Events;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Event executed after processing the request.
 */
class AfterDispatchEvent
{
    public function __construct(
        private ServerRequestInterface $request, private ResponseInterface $response
    ) {
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}