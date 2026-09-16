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
use Puff\Di\Container;
use Puff\Event\Dispatcher;
use Puff\Event\Listener;
use Puff\Event\ServiceProvider;

final class DispatcherTest extends TestCase
{
    protected function setUp(): void
    {
        Recorder::$entries = [];
    }

    public function testDispatchesExactParentAndInterfaceListenersInRegistrationOrder(): void
    {
        $events = new Dispatcher(new Container());
        $events->listen(EventMarker::class, InterfaceListener::class);
        $events->listen(BaseEvent::class, ParentListener::class);
        $events->listen(UserRegistered::class, ExactListener::class);

        $event = new UserRegistered('user-1');

        self::assertSame($event, $events->dispatch($event));
        self::assertSame(['interface', 'parent', 'exact'], Recorder::$entries);
    }

    public function testDeduplicatesListenerRegistration(): void
    {
        $events = new Dispatcher(new Container());
        $events->listen(UserRegistered::class, ExactListener::class);
        $events->listen(UserRegistered::class, ExactListener::class);

        $events->dispatch(new UserRegistered('user-1'));

        self::assertSame(['exact'], Recorder::$entries);
    }

    public function testResolvesListenerDependenciesFromContainer(): void
    {
        $container = new Container();
        $container->instance(Recorder::class, new Recorder('injected'));
        $events = new Dispatcher($container);
        $events->listen(UserRegistered::class, DependencyListener::class);

        $events->dispatch(new UserRegistered('user-1'));

        self::assertSame(['injected'], Recorder::$entries);
    }

    public function testDoesNothingWhenNoListenerMatches(): void
    {
        $event = new UserRegistered('user-1');

        self::assertSame($event, (new Dispatcher(new Container()))->dispatch($event));
        self::assertSame([], Recorder::$entries);
    }

    public function testPropagatesListenerExceptionsAndStopsDispatch(): void
    {
        $events = new Dispatcher(new Container());
        $events->listen(UserRegistered::class, FailingListener::class);
        $events->listen(UserRegistered::class, ExactListener::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('listener failed');
        try {
            $events->dispatch(new UserRegistered('user-1'));
        } finally {
            self::assertSame(['failed'], Recorder::$entries);
        }
    }

    public function testFiberScopedDependenciesDoNotLeakBetweenDispatches(): void
    {
        $container = new Container();
        $container->scoped(ScopeValue::class, static fn (): ScopeValue => new ScopeValue());
        $events = new Dispatcher($container);
        $events->listen(UserRegistered::class, ScopedListener::class);

        $events->dispatch(new UserRegistered('main'));
        $fiber = new \Fiber(static function () use ($events, $container): void {
            $events->dispatch(new UserRegistered('fiber'));
            $container->clearScope();
        });
        $fiber->start();

        self::assertCount(2, Recorder::$entries);
        self::assertNotSame(Recorder::$entries[0], Recorder::$entries[1]);
    }

    public function testProviderRegistersOneDispatcherInstance(): void
    {
        $container = new Container();
        (new ServiceProvider($container))->register();

        self::assertSame($container->make(Dispatcher::class), $container->make(Dispatcher::class));
    }
}

interface EventMarker
{
}

abstract readonly class BaseEvent
{
}

final readonly class UserRegistered extends BaseEvent implements EventMarker
{
    public function __construct(public string $id)
    {
    }
}

final class Recorder
{
    /** @var list<string|int> */
    public static array $entries = [];

    public function __construct(private readonly string $entry = '')
    {
    }

    public function record(): void
    {
        self::$entries[] = $this->entry;
    }
}

final class InterfaceListener implements Listener
{
    public function handle(object $event): void
    {
        Recorder::$entries[] = 'interface';
    }
}

final class ParentListener implements Listener
{
    public function handle(object $event): void
    {
        Recorder::$entries[] = 'parent';
    }
}

final class ExactListener implements Listener
{
    public function handle(object $event): void
    {
        Recorder::$entries[] = 'exact';
    }
}

final class DependencyListener implements Listener
{
    public function __construct(private readonly Recorder $recorder)
    {
    }

    public function handle(object $event): void
    {
        $this->recorder->record();
    }
}

final class FailingListener implements Listener
{
    public function handle(object $event): void
    {
        Recorder::$entries[] = 'failed';
        throw new \RuntimeException('listener failed');
    }
}

final class ScopeValue
{
}

final class ScopedListener implements Listener
{
    public function __construct(private readonly ScopeValue $scope)
    {
    }

    public function handle(object $event): void
    {
        Recorder::$entries[] = \spl_object_id($this->scope);
    }
}
