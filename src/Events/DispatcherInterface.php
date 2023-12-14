<?php declare(strict_types=1);
namespace Phx\Events;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Standardization of events dispatcher.
 */
interface DispatcherInterface extends EventDispatcherInterface
{
    public function listen(string $eventType, callable $listener): void;
}