---
title: Manipulate changes array
weight: 2
---

In some cases you may want to manipulate the changes array before it's saved. The package uses a pipeline approach for this. Changes go through pipes where you can add, edit, or delete from `attributes` and `old` arrays.

```php
use Spatie\Activitylog\Support\EventLogBag;

class RemoveKeyFromLogChangesPipe
{
    public function __construct(protected string $field) {}

    public function handle(EventLogBag $event, Closure $next): EventLogBag
    {
        Arr::forget($event->changes, ["attributes.{$this->field}", "old.{$this->field}"]);

        return $next($event);
    }
}
```

```php
NewsItem::addLogChange(new RemoveKeyFromLogChangesPipe('name'));

$article = NewsItem::create(['name' => 'new article', 'text' => 'new article text']);
$article->update(['name' => 'update article', 'text' => 'update article text']);

Activity::all()->last()->attribute_changes;
/*
    'attributes' => [
        'text' => 'updated text',
    ],
    'old' => [
        'text' => 'original text',
    ]
*/
```

## Adding pipes

A pipe is any class with a `handle(EventLogBag $event, Closure $next): EventLogBag` method. It receives the event data and must call `$next($event)` to pass it to the next pipe.

```php
class YourPipe
{
    public function handle(EventLogBag $event, Closure $next): EventLogBag
    {
        // your changes to the $event

        return $next($event);
    }
}
```

Register a pipe for a model:

```php
YourModel::addLogChange(new YourPipe);
```

To always apply a pipe, register it during model boot:

```php
protected static function booted(): void
{
    static::addLogChange(new YourPipe);
}
```

**Note:** maintain changes in both `attributes` and `old` arrays. Changing one without the other will produce incorrect diffs.
