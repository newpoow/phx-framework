<?php declare(strict_types=1);
namespace Phx\Http\Routing;

use RuntimeException;

/**
 * Routes compiler.
 */
class RouteCompiler
{
    protected const REGEX_COMPILER = '`(\/|\.|)\{([^:\}]*+)(?::([^:\}]*+))?\}(\?|)`';

    /**
     * Compile a route sequence for a regular expression.
     *  e.g.: $rc->compile("/users/{i:id}", ['i' => '[0-9]++']);
     */
    public function compile(string $routeUri, array $patterns = []): string
    {
        $uri = preg_replace_callback('`(?<=^|\})[^.\]\[\?]+?(?=\{|$)`', function ($matches) {
            return preg_quote($matches[0]);
        }, $routeUri);

        $uri = preg_replace_callback(static::REGEX_COMPILER, function ($matches) use ($patterns, $uri) {
            list($block, $pre, $type, $parameter, $optional) = $matches;
            if (!isset($patterns[$type])) {
                throw new RuntimeException(sprintf(
                    "Regular expression for '%s' in block '%s' of route '%s' has not been defined.",
                    $type, $block, $uri
                ));
            }

            $pre = $pre !== '' ? preg_quote($pre) : null;
            $parameter = preg_replace(
                "/[^A-Za-z0-9-]/",
                '',
                empty($parameter) ? $type : $parameter
            );
            $parameter = $parameter !== '' ? "?P<{$parameter}>" : null;
            $optional = $optional !== '' ? '?' : null;

            return "(?:{$pre}({$parameter}{$patterns[$type]})){$optional}";
        }, $uri);

        $uri = $uri === '/' ? '(\/)?' : $uri . '(\/)?';
        return "`^{$uri}$`u";
    }

    /**
     * Construct a URI using a route sequence and parameters.
     *  e.g.: $rc->reverse("/users/{i:id}", ['id' => '10']);
     */
    public function reverse(string $routeUri, array $parameters = []): string
    {
        return preg_replace_callback(static::REGEX_COMPILER, function ($matches) use ($parameters, $routeUri) {
            list($block, $pre, $type, $parameter, $optional) = $matches;

            $parameter = $parameter ?: $type;
            if (array_key_exists($parameter, $parameters)) {
                return $pre . $parameters[$parameter];
            } elseif ($optional) {
                return null;
            }

            throw new RuntimeException(sprintf(
                "The required parameter '%s' in block '%s' of route '%s' has not been defined.",
                $parameter, $block, $routeUri
            ));
        }, $routeUri);
    }
}