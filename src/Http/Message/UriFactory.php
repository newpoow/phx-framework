<?php declare(strict_types=1);
namespace Phx\Http\Message;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Uniform Resource Identifier Factory.
 */
class UriFactory implements UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface
    {
        return static::createFromString($uri);
    }

    public static function createFromString(string $uri): UriInterface
    {
        return new Uri($uri);
    }
}