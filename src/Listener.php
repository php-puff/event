<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/event
 * https://github.com/php-puff/event/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Event;

/** Handles one synchronous in-process domain event. */
interface Listener
{
    public function handle(object $event): void;
}
