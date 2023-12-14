<?php declare(strict_types=1);
namespace Phx\Http\Message;

use Phx\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Response Factory.
 */
class ResponseFactory implements ResponseFactoryInterface
{
    public static function createJsonResponse(
        int $code = 200,
        mixed $data = null,
        array $headers = [],
        int $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
    ): ResponseInterface {
        return new JsonResponse($code, $data, $headers, $flags);
    }

    public static function createHtmlResponse(
        int $code, mixed $body, array $headers = [], string $reasonPhrase = ''
    ): ResponseInterface {
        return new Response($code, $body, $headers, $reasonPhrase);
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return static::createHtmlResponse($code, '', [], $reasonPhrase);
    }
}