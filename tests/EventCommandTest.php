<?php

/*
 * PHP Unison Fiber Framework
 * https://github.com/php-puff/event
 * https://github.com/php-puff/event/issues
 * Copyright (c) Puff
 */

declare(strict_types=1);

namespace Puff\Event\Tests;

use PHPUnit\Framework\TestCase;
use Puff\Console\Generator;
use Puff\Console\Input;
use Puff\Console\Output;
use Puff\Event\EventCommand;

final class EventCommandTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = \sys_get_temp_dir() . '/puff-event-' . \bin2hex(\random_bytes(8));
        \mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testGeneratesEventInDefaultNamespace(): void
    {
        $command = new EventCommand(new Generator($this->root), \dirname(__DIR__) . '/stub/event.stub');
        $output = new Output();

        self::assertSame(0, $command->execute(Input::parse(['UserRegistered']), $output));
        self::assertFileExists($this->root . '/event/UserRegistered.php');
        $source = (string) \file_get_contents($this->root . '/event/UserRegistered.php');
        self::assertStringContainsString('namespace Event;', $source);
        self::assertStringContainsString('public function __construct(public array $data = [])', $source);
    }

    public function testGeneratesEventInSpecifiedNamespace(): void
    {
        $command = new EventCommand(new Generator($this->root), \dirname(__DIR__) . '/stub/event.stub');

        self::assertSame(0, $command->execute(Input::parse(['TokenIssued', 'Domain\\Event']), new Output()));
        self::assertFileExists($this->root . '/domain/Event/TokenIssued.php');
    }

    private function remove(string $path): void
    {
        if (!\is_dir($path)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? \rmdir($file->getPathname()) : \unlink($file->getPathname());
        }
        \rmdir($path);
    }
}
