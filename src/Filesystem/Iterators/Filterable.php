<?php declare(strict_types=1);
namespace Phx\Filesystem\Iterators;

use Closure;
use FilterIterator;
use Iterator;
use OuterIterator;

/**
 * Filter the search by applying anonymous functions.
 */
class Filterable extends FilterIterator
{
    public function __construct(
        protected Iterator $iterator, protected Closure $filter
    ) {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        $iterator = $this->iterator;
        while ($iterator instanceof OuterIterator) {
            $iterator = $iterator->getInnerIterator();
        }

        if (false === call_user_func($this->filter, $iterator)) {
            return false;
        }
        return true;
    }
}