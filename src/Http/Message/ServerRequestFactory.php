<?php declare(strict_types=1);
namespace Phx\Http\Message;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * HTTP Server Request Factory.
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, null, $serverParams);
    }

    public static function createFromGlobals(): ServerRequestInterface
    {
        $request = new ServerRequest(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            self::getUriFromServer(),
            null,
            $_SERVER
        );
        foreach (self::getHeadersFromServer() as $name => $value) {
            $request = $request->withAddedHeader($name, $value);
        }

        if (array_key_exists('SERVER_PROTOCOL', $_SERVER)
            && preg_match('/^HTTP\/(\d(?:\.\d)?)$/', $_SERVER['SERVER_PROTOCOL'], $matches)) {
            $request = $request->withProtocolVersion($matches[1]);
        }

        $request = $request->withUploadedFiles(self::getUploadedFilesFromServer());
        return $request->withCookieParams($_COOKIE)->withParsedBody($_POST)->withQueryParams($_GET);
    }

    protected static function getHeadersFromServer(): array
    {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'REDIRECT_')) {
                $key = substr($key, 9);
                if (array_key_exists($key, $_SERVER)) {
                    continue;
                }
            }

            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtr(strtolower(substr($key, 5)), '_', '-')] = $value;
            } elseif (str_starts_with($key, 'CONTENT_')) {
                $headers['content-' . strtolower(substr($key, 8))] = $value;
            }
        }
        return $headers;
    }

    protected static function getUploadedFilesFromServer(): array
    {
        $walker = function ($path, $size, $error, $name, $type) use (&$walker) {
            if (!is_array($path)) {
                return (new UploadedFileFactory())->createUploadedFile(
                    StreamFactory::createFromFile($path, 'rb'),
                    $size, $error, $name, $type
                );
            }

            $files = array();
            foreach ($path as $key => $value) {
                $files[$key] = $walker(
                    $value, $size[$key], $error[$key], $name[$key], $type[$key]
                );
            }
            return $files;
        };

        $files = array();
        foreach ($_FILES as $field => $metadata) {
            $files[$field] = $walker(
                $metadata['tmp_name'], $metadata['size'], $metadata['error'], $metadata['name'], $metadata['type']
            );
        }
        return $files;
    }

    protected static function getUriFromServer(): UriInterface
    {
        $server = array_change_key_case($_SERVER, CASE_LOWER);
        $scheme = 'http';
        if (array_key_exists('https', $server) &&
            ((true === $server['https'])
                || 'on' === strtolower($server['https']))) {
            $scheme = 'https';
        }

        $host = 'localhost';
        if (array_key_exists('http_host', $server)) {
            $host = $server['http_host'];
        } elseif (array_key_exists('server_name', $server)) {
            $host = $server['server_name'];
            if (array_key_exists('server_port', $server)) {
                $host .= ':' . $server['server_port'];
            }
        }

        $target = '/';
        if (array_key_exists('request_uri', $server)) {
            $target = $server['request_uri'];
        } elseif (array_key_exists('php_self', $server)) {
            $target = $server['php_self'];
            if (array_key_exists('query_string', $server)) {
                $target .= '?' . ltrim($server['query_string'], '?');
            }
        }
        return UriFactory::createFromString($scheme . '://'. $host . $target);
    }
}