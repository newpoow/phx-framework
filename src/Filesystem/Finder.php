<?php declare(strict_types=1);
namespace Phx\Filesystem;

use AppendIterator;
use Closure;
use Countable;
use FilesystemIterator;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use Phx\Filesystem\Iterators\Filterable;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Traversable;

/**
 * Directory and file finder using filtering rules.
 */
class Finder implements Countable, IteratorAggregate
{
    protected const ONLY_FILES = 1;
    protected const ONLY_DIRECTORIES = 2;

    protected array $filters = [];
    protected array $paths = [];

    public static function create(): static
    {
        return new static();
    }

    public static function directories(): static
    {
        return new static(self::ONLY_DIRECTORIES);
    }

    public static function files(): static
    {
        return new static(self::ONLY_FILES);
    }

    public function __construct(
        protected ?int $mode = null,
        protected int $maxDepth = -1,
        protected bool $links = false
    ) {
    }

    public function depth(int $level): static
    {
        $this->maxDepth = $level;
        return $this;
    }

    public function filter(Closure $closure): static
    {
        $this->filters[] = $closure;
        return $this;
    }

    public function followLinks(): static
    {
        $this->links = true;
        return $this;
    }

    public function in(...$paths): static
    {
        if (count($this->paths) !== 0) {
            throw new LogicException("Directory to search has already been specified.");
        }

        $this->paths = array_map(function ($path) {
            if (is_string($path) && is_dir($path)) {
                return $path;
            }

            throw new InvalidArgumentException(sprintf(
                "The '%s' directory does not exist.", $path
            ));
        }, $paths && is_array($paths[0]) ? $paths[0] : $paths);
        return $this;
    }

    ##+++++++++++++++ FILTER METHODS +++++++++++++++##

    public function name(...$pattern): static
    {
        $pattern = $this->buildPattern($pattern && is_array($pattern[0]) ? $pattern[0] : $pattern);
        $this->filter(function (RecursiveDirectoryIterator $iterator) use ($pattern) {
            return is_null($pattern) || preg_match($pattern, '/' . strtr($iterator->getSubPathName(), '\\', '/'));
        });
        return $this;
    }

    ##+++++++++++++ PROTECTED METHODS ++++++++++++++##

    protected function buildPattern(array $masks): ?string
    {
        $pattern = array();
        foreach ($masks as $mask) {
            $mask = rtrim(strtr($mask, '\\', '/'), '/');
            if ('' === $mask) continue;
            if ($mask === '*') return null;

            $prefix = '';
            if ($mask[0] === '/') {
                $mask = ltrim($mask, '/');
                $prefix = '(?<=^/)';
            }
            $pairs = array(
                '\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]',
                '\[\!' => '[^', '\[' => '[', '\]' => ']', '\-' => '-'
            );
            $pattern[] = $prefix . strtr(preg_quote($mask, '#'), $pairs);
        }
        return $pattern ? '#/(' . implode('|', $pattern) . ')\z#i' : null;
    }

    protected function search(string $path): Filterable|RecursiveIteratorIterator|RecursiveDirectoryIterator
    {
        $flags = FilesystemIterator::SKIP_DOTS;
        if ($this->links) {
            $flags |= FilesystemIterator::FOLLOW_SYMLINKS;
        }

        $iterator = new RecursiveDirectoryIterator($path, $flags);
        if ($this->maxDepth !== 0) {
            $iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
            $iterator->setMaxDepth($this->maxDepth);
        }

        if ($this->mode) {
            $iterator = new Filterable($iterator, function (RecursiveDirectoryIterator $iterator) {
                return (self::ONLY_DIRECTORIES === (self::ONLY_DIRECTORIES & $this->mode) && $iterator->isDir())
                    || (self::ONLY_FILES === (self::ONLY_FILES & $this->mode) && $iterator->isFile());
            });
        }

        foreach ($this->filters as $filter) {
            $iterator = new Filterable($iterator, $filter);
        }
        return $iterator;
    }

    ##+++++++++++++ COUNTABLE METHODS ++++++++++++++##

    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    ##+++++++++ ITERATOR AGGREGATE METHODS +++++++++##

    public function getIterator(): Traversable
    {
        if (count($this->paths) === 0) {
            throw new LogicException("Call in() to specify directory to search.");
        }

        $iterator = new AppendIterator();
        foreach ($this->paths as $path) {
            $iterator->append($this->search(strval($path)));
        }
        return $iterator;
    }
}