---
title: Manipulate changes array
weight: 2
---

In some cases you may want to manipulate/control changes array, v4 made this possible by introducing new pipeline approach. Changes array will go through pipes carried over by the event object. In every pipe you can add, edit or delete from `attribute` and `old` arrays. See example:

```php
// RemoveKeyFromLogChangesPipe.php

use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;

class RemoveKeyFromLogChangesPipe implements LoggablePipe
{
    public function __construct(protected string $field){}

    public function handle(EventLogBag $event, Closure $next): EventLogBag
    {
        Arr::forget($event->changes, ["attributes.{$this->field}", "old.{$this->field}"]);

        return $next($event);
    }
}
```

```php
// ... in your controller/job/middleware

NewsItem::addLogChange(new RemoveKeyFromLogChangesPipe('name'));

$article = NewsItem::create(['name' => 'new article', 'text' => 'new article text']);
$article->update(['name' => 'update article', 'text' => 'update article text']);

Activity::all()->last()->changes();
/*
    'attributes' => [
        'text' => 'updated text',
    ],
    'old' => [
        'text' => 'original text',
    ]
*/
```

By adding i.e. `RemoveKeyFromLogChangesPipe` pipe every time log NewsItem is changed the result event will run through this pipe removing the specified key from changes array.

**Note** you need to maintain changes in both `attribute` and `old` array because changing one without the other will screw changing diffs and the information will be pointless!

## Add pipes

Every pipe should implement `Spatie\Activitylog\Contracts\LoggablePipe` that enforces `handle()` method that will receive `Spatie\Activitylog\EventLogBag` and the next pipe. Your pipe must return the next pipe passing the event applying your changes `return $next($event)`.

```php

class YourPipe implements LoggablePipe
{
    public function handle(EventLogBag $event, Closure $next): EventLogBag
    {
        // your changes to the $event

        return $next($event);
    }
}

```
Then you can apply this when calling your model with:
```php
YourModel::addLogChange(new YourPipe);
```

However, you may wish to ensure it's always called within the model and as such you could apply it during model boot with the following:

```php
protected static function booted(): void
{
    static::addLogChange(new YourPipe);
}
```

