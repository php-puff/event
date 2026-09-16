<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/event
 * https://github.com/php-puff/event/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Event;

use Puff\Console\Contract;
use Puff\Console\Generator;
use Puff\Console\Input;
use Puff\Console\Output;

/** Generates an application domain-event class. */
final readonly class EventCommand implements Contract
{
    public function __construct(
        private Generator $generator,
        private string $stub,
    ) {
    }

    public function name(): string
    {
        return 'event';
    }

    public function description(): string
    {
        return 'Create a Puff domain event';
    }

    public function usage(): string
    {
        return 'event <name> [namespace] [options]';
    }

    public function valueOptions(): array
    {
        return ['namespace' => 'N'];
    }

    public function flagOptions(): array
    {
        return ['force' => 'f'];
    }

    public function execute(Input $input, Output $output): int
    {
        $name = (string) $input->argument(0);
        if ($name === '') {
            throw new \InvalidArgumentException('Event name is required.');
        }

        $namespace = (string) ($input->option('namespace') ?: $input->argument(1) ?: 'Event');
        $class = $this->generator->generate(
            $name,
            $namespace,
            $this->stub,
            $input->hasOption('force'),
        );
        $output->write("{$class} created successfully.");

        return 0;
    }
}
