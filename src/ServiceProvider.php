<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/event
 * https://github.com/php-puff/event/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Event;

use Puff\Di\ServiceProvider as Provider;

/** Registers the process-wide event dispatcher. */
final class ServiceProvider extends Provider
{
    public function register(): void
    {
        $this->app->singleton(Dispatcher::class, fn (): Dispatcher => new Dispatcher($this->app));
    }
}
