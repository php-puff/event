<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/event
 * https://github.com/php-puff/event/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Event;

use Puff\Di\Container;

/** Dispatches in-process events to DI-resolved listeners. */
final class Dispatcher
{
    /** @var list<array{event: class-string, listener: class-string<Listener>}> */
    private array $listeners = [];

    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param class-string $event
     * @param class-string $listener
     */
    public function listen(string $event, string $listener): void
    {
        if (!\class_exists($event) && !\interface_exists($event)) {
            throw new \InvalidArgumentException("Event [{$event}] does not exist.");
        }
        if (!\is_a($listener, Listener::class, true)) {
            throw new \InvalidArgumentException("Listener [{$listener}] must implement " . Listener::class . '.');
        }
        foreach ($this->listeners as $registered) {
            if ($registered['event'] === $event && $registered['listener'] === $listener) {
                return;
            }
        }
        $this->listeners[] = ['event' => $event, 'listener' => $listener];
    }

    public function dispatch(object $event): object
    {
        foreach ($this->listeners as $registered) {
            if (!\is_a($event, $registered['event'])) {
                continue;
            }

            $listener = $this->container->make($registered['listener']);
            if (!$listener instanceof Listener) {
                throw new \LogicException("Listener [{$registered['listener']}] is invalid.");
            }
            $listener->handle($event);
        }

        return $event;
    }
}
