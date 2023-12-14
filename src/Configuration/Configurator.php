<?php declare(strict_types=1);
namespace Phx\Configuration;

use InvalidArgumentException;

/**
 * Configuration manager.
 */
final class Configurator
{
    protected array $items = [];
    protected array $parsers = [];

    ##+++++++++++++++ PUBLIC METHODS +++++++++++++++##

    public function __construct(
        protected ?string $separator = null, array $items = []
    ) {
        $this->items = $this->normalize($items);
    }

    public function addParser(ParserInterface $parser, array|string $extensions): Configurator
    {
        $extensions = is_array($extensions) ? $extensions : [$extensions];
        foreach ($extensions as $extension) {
            $this->parsers[strtolower($extension)] = $parser;
        }
        return $this;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function get(string $key, $default = null)
    {
        $data = $this->items;
        if ($this->separator) {
            $key = explode($this->separator, rtrim($key, $this->separator));
        }

        foreach ((array)$key as $step) {
            if (!is_array($data) || !array_key_exists($step, $data)) {
                return $default;
            }
            $data = &$data[$step];
        }
        return $data;
    }

    public function getParser(string $extension): ParserInterface
    {
        $extension = strtolower($extension);
        if (!array_key_exists($extension, $this->parsers)) {
            throw new InvalidArgumentException(sprintf(
                "There is no parser for the '%s' extension.", $extension
            ));
        }
        return $this->parsers[$extension];
    }

    public function getParsers(): array
    {
        return $this->parsers;
    }

    public function has(string $key): bool
    {
        $data = $this->items;
        if (empty($this->separator)) {
            return array_key_exists($key, $data);
        }

        $segments = explode($this->separator, $key);
        while (count($segments) > 0) {
            $segment = array_shift($segments);
            if (!isset($data[$segment])) {
                return false;
            }
            $data = &$data[$segment];
        }
        return true;
    }

    public function load(...$files): Configurator
    {
        $files = isset($files[0]) && is_array($files[0]) ? $files[0] : $files;
        foreach ($files as $name => $file) {
            if (($path = realpath($file)) === false) continue;

            if (!is_string($name) || empty(trim($name))) {
                $name = pathinfo($path, PATHINFO_FILENAME);
            }
            $this->set(strval($name), $this->getParser(pathinfo($path, PATHINFO_EXTENSION))->parse($path));
        }
        return $this;
    }

    public function remove(...$keys): Configurator
    {
        $keys = isset($keys[0]) && is_array($keys[0]) ? $keys[0] : $keys;
        foreach ($keys as $key) {
            $data = &$this->items;

            $segments = $this->separator ? explode($this->separator, $key) : [$key];
            while (count($segments) > 1) {
                $segment = array_shift($segments);
                if (!isset($data[$segment])) {
                    continue 2;
                }
                $data = &$data[$segment];
            }
            unset($data[array_shift($segments)]);
        }
        return $this;
    }

    public function set($key, $value = null): Configurator
    {
        $this->items = array_replace_recursive(
            $this->items, $this->normalize(is_array($key) ? $key : array(strval($key) => $value))
        );
        return $this;
    }

    ##+++++++++++++ PROTECTED METHODS ++++++++++++++##

    protected function normalize(array $dotted): array
    {
        if (empty($this->separator)) {
            return $dotted;
        }

        $data = array();
        foreach ($dotted as $key => $value) {
            $value = is_array($value) ? $this->normalize($value) : $value;
            if (!str_contains(strval($key), $this->separator)) {
                $data[$key] = $value;
                continue;
            }

            $temp = &$data;
            $segments = explode($this->separator, $key);

            while (count($segments) > 0) {
                $segment = array_shift($segments);
                if (!isset($temp[$segment]) || !is_array($temp[$segment])) {
                    $temp[$segment] = array();
                }
                $temp = &$temp[$segment];
            }
            $temp = $value;
        }
        return $data;
    }
}