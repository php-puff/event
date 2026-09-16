<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/event
 * https://github.com/php-puff/event/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Event;

use Psr\Container\ContainerInterface;
use Puff\Console\CommandProvider;
use Puff\Console\Contract;
use Puff\Console\Generator;

/** Registers event source-generation commands. */
final class ConsoleProvider implements CommandProvider
{
    /** @return iterable<Contract> */
    public function commands(string $root, ContainerInterface $container): iterable
    {
        yield new EventCommand(new Generator($root), \dirname(__DIR__) . '/stub/event.stub');
    }
}
