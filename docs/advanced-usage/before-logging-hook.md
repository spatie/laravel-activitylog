---
title: Before logging hook
weight: 8
---

You can register global callbacks that run on every activity right before it is saved. This is useful for enriching activities with extra data without creating a custom Activity model.

## Registering a callback

Call `beforeLogging` on the `Activity` facade, typically in a service provider's `boot` method:

```php
use Spatie\Activitylog\Facades\Activity;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Activity::beforeLogging(function (\Spatie\Activitylog\Contracts\Activity $activity) {
            $activity->properties = $activity->properties->put('ip', request()->ip());
        });
    }
}
```

Every activity (both manual `activity()->log()` calls and automatic model event logging) will now include the IP address in its properties.

## Multiple callbacks

You can register multiple callbacks. They run in the order they were registered:

```php
Activity::beforeLogging(function ($activity) {
    $activity->properties = $activity->properties->put('ip', request()->ip());
});

Activity::beforeLogging(function ($activity) {
    $activity->properties = $activity->properties->put('user_agent', request()->userAgent());
});
```

## Example: batch UUID

If you need to group related activities together (for example, all activities from a single request), you can use the hook to assign a shared identifier:

**1. Add the column:**

```php
Schema::table('activity_log', function (Blueprint $table) {
    $table->uuid('batch_uuid')->nullable()->index();
});
```

**2. Register the hook:**

```php
use Illuminate\Support\Str;
use Spatie\Activitylog\Facades\Activity;

// In your AppServiceProvider::boot()
$batchUuid = (string) Str::uuid();

Activity::beforeLogging(function ($activity) use ($batchUuid) {
    $activity->batch_uuid = $batchUuid;
});
```

All activities logged during the request will share the same `batch_uuid`.

## Alternative registration

You can also register callbacks directly on the `LogActivityAction` class:

```php
use Spatie\Activitylog\Actions\LogActivityAction;

LogActivityAction::beforeLogging(function ($activity) {
    // ...
});
```
