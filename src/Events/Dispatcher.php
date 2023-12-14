<?php declare(strict_types=1);
namespace Phx\Events;

use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Events dispatcher.
 */
final class Dispatcher implements DispatcherInterface, ListenerProviderInterface
{
    private array $listeners = [];
    private array $priorities = [];
    private array $providers = [];

    ##+++++++++++++++ PUBLIC METHODS +++++++++++++++##

    public function __construct()
    {
        $this->addProvider($this);
    }

    public function addProvider(ListenerProviderInterface $provider): Dispatcher
    {
        $hash = spl_object_hash($provider);
        if (!array_key_exists($hash, $this->providers)) {
            $this->providers[$hash] = $provider;
        }
        return $this;
    }

    public function dispatch(object $event): object
    {
        foreach ($this->providers as $provider) {
            foreach ($provider->getListenersForEvent($event) as $listener) {
                if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                    return $event;
                }
                call_user_func($listener, $event);
            }
        }
        return $event;
    }

    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->priorities as $priority) {
            foreach ($this->listeners[$priority] as $type => $listener) {
                if ($event instanceof $type) yield from $listener;
            }
        }
    }

    public function listen(string $eventType, callable $listener, int $priority = 1000): void
    {
        $priority = "$priority.0";
        if (!array_key_exists($priority, $this->listeners)) {
            $this->listeners[$priority] = [$eventType => []];

            $this->sortPriorities();
        }

        if (!in_array($listener, $this->listeners[$priority][$eventType], true)) {
            $this->listeners[$priority][$eventType][] = $listener;
        }
    }

    ##+++++++++++++ PRIVATE METHODS ++++++++++++++++##

    private function sortPriorities(): void
    {
        $this->priorities = array_keys($this->listeners);
        usort($this->priorities, static fn($a, $b) => $a <=> $b);
    }
}