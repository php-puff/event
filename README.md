# Puff Event

`puff/event` is a synchronous, type-safe, in-process domain event dispatcher.

```php
use Puff\Event\Dispatcher;
use Puff\Event\Listener;

final readonly class UserRegistered
{
    public function __construct(public string $id)
    {
    }
}

final class WriteAuditLog implements Listener
{
    public function handle(object $event): void
    {
        if (!$event instanceof UserRegistered) {
            return;
        }

        // Write the audit log.
    }
}

$events->listen(UserRegistered::class, WriteAuditLog::class);
$events->dispatch(new UserRegistered('user-1'));
```

The dispatcher is registered automatically through Composer discovery. Inject
`Puff\Event\Dispatcher` into services or register listeners from an application
service provider.

Listeners run synchronously in registration order. An exception stops dispatch
and is propagated to the caller. The component does not provide queues, retries,
durable delivery, delayed events or cross-process messaging; use `puff/job` for
background work.
