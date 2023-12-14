<?php declare(strict_types=1);
namespace Phx\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Represents a Uniform Resource Identifier - URI.
 */
class Uri implements UriInterface
{
    protected string $scheme = '';
    protected string $host = '';
    protected string $path = '';
    protected ?int $port = null;
    protected string $query = '';
    protected string $fragment = '';
    protected string $userinfo = '';

    public function __construct(string $uri = '')
    {
        if (!empty($uri)) {
            $this->parseUri($uri);
        }
    }

    public function getAuthority(): string
    {
        $authority = $this->getHost();
        if (empty($authority)) {
            return '';
        }

        if (!empty($info = $this->getUserInfo())) {
            $authority = "{$info}@{$authority}";
        }

        if (!is_null($port = $this->getPort())) {
            $authority = "{$authority}:{$port}";
        }
        return $authority;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPort(): ?int
    {
        $scheme = $this->getScheme();
        if ((80 === $this->port && 'http' === $scheme) || (443 === $this->port && 'https' === $scheme)) {
            return null;
        }
        return $this->port;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getUserInfo(): string
    {
        return $this->userinfo;
    }

    public function withFragment(string $fragment): UriInterface
    {
        if ($fragment && (0 === stripos($fragment, '#'))) {
            $fragment = '%23' . substr($fragment, 1);
        }

        $cloned = clone $this;
        $cloned->fragment = $this->normalizeQueryOrFragment($fragment);
        return $cloned;
    }

    public function withHost(string $host): UriInterface
    {
        $cloned = clone $this;
        $cloned->host = strtolower($host);
        return $cloned;
    }

    public function withPath(string $path): UriInterface
    {
        if (false !== stripos($path, '?') || false !== stripos($path, '#')) {
            throw new InvalidArgumentException(sprintf(
                "The path '%s' must not contain query parameters or hash fragment.", $path
            ));
        }

        $cloned = clone $this;
        $cloned->path = $this->normalizePath($path);
        return $cloned;
    }

    public function withPort(?int $port): UriInterface
    {
        $port = !is_null($port) ? $port : null;
        if (is_int($port) && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException(sprintf(
                "Invalid port: '%s'. Must be a valid integer within TCP/UDP port range", $port
            ));
        }

        $cloned = clone $this;
        $cloned->port = $port;
        return $cloned;
    }

    public function withQuery(string $query): UriInterface
    {
        if (false !== stripos($query, '#')) {
            throw new InvalidArgumentException(sprintf(
                "The query '%s' must not contain hash fragment.", $query
            ));
        }

        $cloned = clone $this;
        $cloned->query = $this->normalizeQueryOrFragment($query);
        return $cloned;
    }

    public function withScheme(string $scheme): UriInterface
    {
        if ($scheme && !preg_match('/^(?:[A-Za-z][0-9A-Za-z\+\-\.]*)?$/', $scheme)) {
            throw new InvalidArgumentException(sprintf(
                "The scheme '%s' is invalid.", $scheme
            ));
        }

        $cloned = clone $this;
        $cloned->scheme = strtolower($scheme);
        return $cloned;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        if (is_string($password) && !empty($password)) {
            $user .= ':' . $password;
        }

        $cloned = clone $this;
        $cloned->userinfo = $user;
        return $cloned;
    }

    public function __toString(): string
    {
        $uri = '';
        if (!empty($scheme = $this->getScheme())) {
            $uri .= $scheme . ':';
        }

        if (!empty($authority = $this->getAuthority())) {
            $uri .= '//' . $authority;
        }

        if (!empty($path = $this->getPath())) {
            $uri .= $path;
        }

        if (!empty($query = $this->getQuery())) {
            $uri .= '?' . $query;
        }

        if (!empty($fragment = $this->getFragment())) {
            $uri .= '#' . $fragment;
        }
        return $uri;
    }

    ##+++++++++++++ PROTECTED METHODS ++++++++++++++##

    protected function normalizePath(string $path): string
    {
        $pattern = '#(?:[^a-zA-Z0-9_\-\.~\pL:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))#u';
        $path = preg_replace_callback($pattern, function ($matches) {
            return rawurlencode($matches[0]);
        }, $path);

        if ($path && ('/' === $path[0])) {
            $path = ('/' . ltrim($path, '/'));
        }
        return $path;
    }

    protected function normalizeQueryOrFragment(string $queryOrFragment): string
    {
        $pattern = '#(?:[^a-zA-Z0-9_\-\.~\pL!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))#u';
        return preg_replace_callback($pattern, function ($matches) {
            return rawurlencode($matches[0]);
        }, $queryOrFragment);
    }

    protected function parseUri(string $uri)
    {
        if (!is_array($parsed = parse_url($uri))) {
            throw new InvalidArgumentException(sprintf(
                "The source URI string appears to be malformed: '%s'.", $uri
            ));
        }

        if (array_key_exists('scheme', $parsed)) {
            $this->scheme = strtolower($parsed['scheme']);
        }

        if (array_key_exists('user', $parsed)) {
            $this->userinfo = array_key_exists('pass', $parsed) ?
                "{$parsed['user']}:{$parsed['pass']}" : $parsed['user'];
        }

        if (array_key_exists('host', $parsed)) {
            $this->host = strtolower($parsed['host']);
        }

        if (array_key_exists('port', $parsed)) {
            $this->port = $parsed['port'];
        }

        if (array_key_exists('path', $parsed)) {
            $this->path = $this->normalizePath($parsed['path']);
        }

        if (array_key_exists('query', $parsed)) {
            $this->query = $this->normalizeQueryOrFragment($parsed['query']);
        }

        if (array_key_exists('fragment', $parsed)) {
            $this->fragment = $this->normalizeQueryOrFragment($parsed['fragment']);
        }
    }
}