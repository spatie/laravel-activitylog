---
title: Logging model events
weight: 1
---

The package can automatically log events such as when a model is created, updated and deleted. To make this work all you need to do is let your model use the `Spatie\Activitylog\Models\Concerns\LogsActivity` trait.

The simplest usage requires no configuration at all:

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class NewsItem extends Model
{
    use LogsActivity;
}
```

This will log `created`, `updated`, and `deleted` events, but won't track attribute changes. To also track attribute changes, override the `getActivitylogOptions()` method. It should return a `LogOptions` instance built from `LogOptions::defaults()` using fluent methods.

The attributes that need to be logged can be defined either by their name or you can put in a wildcard `['*']` to log any attribute that has changed.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'text'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name', 'text']);
    }
}
```

Note that we start from sensible defaults, but any of them can be overridden as needed by chaining fluent methods. Review the `Spatie\Activitylog\Support\LogOptions` class for full list of supported options.

## Basics of Logging Configuration

If you want to log changes to all the `$fillable` attributes of the model, you can chain `->logFillable()` on the `LogOptions` class.

Alternatively, if you have a lot of attributes and used `$guarded` instead of `$fillable` you can also chain `->logUnguarded()` to add all attributes that are not listed in `$guarded`.

These can be combined with each other and with `->logOnly()`. The final set of logged attributes is the union of all sources.

## Basic example of what is logged

Let's see what gets logged when creating an instance of that model.

```php
$newsItem = NewsItem::create([
   'name' => 'original name',
   'text' => 'Lorem'
]);

//creating the newsItem will cause an activity being logged
$activity = Activity::all()->last();

$activity->description; //returns 'created'
$activity->subject; //returns the instance of NewsItem that was created
$activity->attribute_changes; //returns a collection containing ['attributes' => ['name' => 'original name', 'text' => 'Lorem']];
```

Now let's update that `$newsItem`.

```php
$newsItem->name = 'updated name';
$newsItem->save();

//updating the newsItem will cause an activity being logged
$activity = Activity::all()->last();

$activity->description; //returns 'updated'
$activity->subject; //returns the instance of NewsItem that was created
```

Calling `$activity->attribute_changes` will return a collection containing:

```php
[
   'attributes' => [
        'name' => 'updated name',
        'text' => 'Lorem',
    ],
    'old' => [
        'name' => 'original name',
        'text' => 'Lorem',
    ],
];
```

Pretty cool, right?

Now, what happens when you call delete?

```php
$newsItem->delete();

//deleting the newsItem will cause an activity being logged
$activity = Activity::all()->last();

$activity->description; //returns 'deleted'
$activity->attribute_changes; //returns a collection containing ['old' => ['name' => 'updated name', 'text' => 'Lorem']];
```

## Customizing the events being logged

By default the package will log the `created`, `updated`, `deleted` events. You can modify this behaviour by setting the `$recordEvents` property on a model.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class NewsItem extends Model
{
    use LogsActivity;

    //only the `deleted` event will get logged automatically
    protected static $recordEvents = ['deleted'];
}
```

Alternatively, you can use `$doNotRecordEvents` to exclude specific events while keeping all others.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class NewsItem extends Model
{
    use LogsActivity;

    //the `created` event will not be logged
    protected static $doNotRecordEvents = ['created'];
}
```

## Customizing the description

By default the package will log `created`, `updated`, `deleted` in the description of the activity. You can modify this text by providing a callback to the `->setDescriptionForEvent()` method on the `LogOptions` class.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'text'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}");
    }
}
```

Let's see what happens now:

```php
$newsItem = NewsItem::create([
   'name' => 'original name',
   'text' => 'Lorem'
]);

//creating the newsItem will cause an activity being logged
$activity = Activity::all()->last();

$activity->description; //returns 'This model has been created'
```

## Customizing the log name

You can pass a string to `->useLogName()` to make the model use a different log name than the default.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('system');
    }
}
```

## Skipping logging when only certain attributes change

If your model contains attributes whose changes alone don't need to trigger an activity being logged, you can use `->dontLogIfAttributesChangedOnly()`.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'text'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name', 'text'])
        ->dontLogIfAttributesChangedOnly(['text']);
    }
}
```

Changing only `text` will not trigger an activity being logged. If both `name` and `text` change, the activity will still be logged (and both attributes will appear in the changes).

By default the `updated_at` attribute is _not_ ignored and will trigger an activity being logged. You can add the `updated_at` attribute to the `->dontLogIfAttributesChangedOnly()` array to override this behavior.

## Logging only the changed attributes

If you do not want to log every attribute passed into `->logOnly()`, but only those that have actually changed after the update, you can call `->logOnlyDirty()`.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'text'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name', 'text'])
        ->logOnlyDirty();
    }
}
```

Changing only `name` means only the `name` attribute will be logged in the activity, and `text` will be left out.

## Logging directly related model attributes

If you would like to log an attribute of a directly related model, you may use dot notation to log an attribute of the model's relationship.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'text', 'user_id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name', 'text', 'user.name']);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

## Logging only a specific JSON attribute sub-key

If you would like to log only the changes to specific sub-keys of a JSON column, you can use arrow notation in `->logOnly()`.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    protected $fillable = ['preferences', 'name'];

    protected $casts = [
        'preferences' => 'collection' // casting the JSON database column
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['preferences->notifications->status', 'preferences->hero_url']);
    }
}
```

Only the specified sub-keys will be logged. Other keys inside `preferences` will be left out.

Here's an example:

```php
// Create a news item.
$newsItem = NewsItem::create([
    'name' => 'Title',
    'preferences' => [
        'notifications' => [
            'status' => 'off',
        ],
        'hero_url' => ''
    ],
]);

// Update the json object
$newsItem->update([
    'preferences' => [
        'notifications' => [
            'status' => 'on',
        ],
        'hero_url' => 'http://example.com/hero.png'
    ],
]);

$lastActivity = Activity::latest()->first();

$lastActivity->attribute_changes->toArray();
```

```php
// output
[
    "attributes" => [
        "preferences" => [
            "notifications" => [
                "status" => "on",
            ],
            "hero_url" => "http://example.com/hero.png",
        ],
    ],
    "old" => [
        "preferences" => [
            "notifications" => [
                "status" => "off",
            ],
            "hero_url" => "",
        ],
    ],
]
```

## Prevent saving logs that have no changed attribute

Calling `->dontLogEmptyChanges()` prevents the package from storing empty logs. Empty logs can occur when you're tracking specific attributes but none of them actually changed in a given update.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'text'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['text'])
        ->logOnlyDirty()
        ->dontLogEmptyChanges();
    }
}
```

## Excluding attributes from the log output

If you use wildcard logging but want to exclude certain attributes from appearing in the logged output, use `->logExcept()`:

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logAll()
        ->logExcept(['password', 'remember_token']);
    }
}
```

The `password` and `remember_token` attributes will never appear in the logged changes.

Note: `logExcept()` removes attributes from the log output. This is different from `dontLogIfAttributesChangedOnly()` which prevents the entire activity from being created when only the specified attributes changed.

## Using the CausesActivity trait

The package ships with a `Spatie\Activitylog\Models\Concerns\CausesActivity` trait which can be added to any model that you use as a causer. It provides an `activitiesAsCauser()` relationship which returns all activities that are caused by the model.

If you include it in the `User` model you can retrieve all the current user's activities like this:

```php
Auth::user()->activitiesAsCauser;
```

## Using the HasActivity trait

If your model both causes and logs activities (common for User models), use the `HasActivity` trait which combines `LogsActivity` and `CausesActivity`:

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

class User extends Model
{
    use HasActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }
}
```

This provides three relationships:
- `activities()` returns activities where this model is the subject (alias for `activitiesAsSubject()`)
- `activitiesAsSubject()` returns activities where this model is the subject
- `activitiesAsCauser()` returns activities where this model is the causer

## Using the ActivityEvent enum

The package provides an `ActivityEvent` enum for the built-in event types:

```php
use Spatie\Activitylog\Enums\ActivityEvent;

ActivityEvent::Created;  // 'created'
ActivityEvent::Updated;  // 'updated'
ActivityEvent::Deleted;  // 'deleted'
ActivityEvent::Restored; // 'restored'
```

You can use this enum when querying activities or when manually logging:

```php
Activity::forEvent(ActivityEvent::Created)->get();

activity()->event(ActivityEvent::Updated)->log('...');
```

All methods that accept an event also accept a plain string, so custom event names still work.

## Logging restored events

If your model uses the `SoftDeletes` trait, the package will automatically log `restored` events in addition to `created`, `updated`, and `deleted`. No extra configuration is needed.

## Querying activity

The `Activity` model provides several query scopes for filtering activities:

```php
use Spatie\Activitylog\Models\Activity;

// get all activities for a specific subject
Activity::forSubject($newsItem)->get();

// get all activities caused by a specific user
Activity::causedBy($user)->get();

// get all activities for a specific event
Activity::forEvent('updated')->get();

// these scopes can be combined
Activity::forSubject($newsItem)->causedBy($user)->forEvent('updated')->get();
```

The `inLog` scope is documented in [using multiple logs](/docs/laravel-activitylog/v5/advanced-usage/using-multiple-logs).

## Disabling logging on demand

You can also disable logging for a specific model at runtime. To do so, you can use the `disableLogging()` method:

```php
$newsItem = NewsItem::create([
   'name' => 'original name',
   'text' => 'Lorem'
]);

// Updating with logging disabled
$newsItem->disableLogging();

$newsItem->update(['name' => 'The new name is not logged']);
```

You can also chain `disableLogging()` with the `update()` method.

### Enable logging again

You can use the `enableLogging()` method to re-enable logging.

```php
$newsItem = NewsItem::create([
   'name' => 'original name',
   'text' => 'Lorem'
]);

// Updating with logging disabled
$newsItem->disableLogging();

$newsItem->update(['name' => 'The new name is not logged']);

// Updating with logging enabled
$newsItem->enableLogging();

$newsItem->update(['name' => 'The new name is logged']);
```

## Tap Activity before logged from event

In addition to the `tap()` method on `ActivityLogger`, you can use the `beforeActivityLogged()` method on your model. This allows you to fill properties and add custom fields before the activity is saved.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class NewsItem extends Model
{
    use LogsActivity;

    public function beforeActivityLogged(Activity $activity, string $eventName)
    {
        $activity->description = "activity.logs.message.{$eventName}";
    }
}
```

## Logging on Pivot Models

Sometimes you want to log changes on your pivot model, for example if it contains additional data.
By default pivot models don't have a primary key/column and because of this can't be used in eloquent relations.
To solve this you have to add a primary key column `id` to your pivot table (`$table->id()`) and configure your pivot model to use this primary key.

```php
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

final class PivotModel extends Pivot
{
    use LogsActivity;

    public $incrementing = true;
}
```

After these changes you can log activities on your pivot models as expected.
