---
title: Logging model events
weight: 1
---

The package can automatically log events such as when a model is created, updated and deleted. To make this work all you need to do is let your model use the `Spatie\Activitylog\Traits\LogsActivity`-trait.

As a bonus the package will also log the changed attributes for all these events when you define our own options method.

The trait contains an abstract method `getActivitylogOptions()` that you can use to customize options. It needs to return a `LogOptions` instance built from `LogOptions::defaults()` using fluent methods.

The attributes that need to be logged can be defined either by their name or you can put in a wildcard `['*']` to log any attribute that has changed.

Here's an example:

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'text'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name', 'text']);
        // Chain fluent methods for configuration options
    }
}
```

Note that we start from sensible defaults, but any of them can be overridden as needed by chaining fluent methods. Review the `Spatie\Activitylog\LogOptions` class for full list of supported options.

## Basics of Logging Configuration

If you want to log changes to all the `$fillable` attributes of the model, you can chain `->logFillable()` on the `LogOptions` class.

Alternatively, if you have a lot of attributes and used `$guarded` instead of `$fillable` you can also chain `->logUnguarded()` to add all attributes that are not listed in `$guarded`.

For both of these flags it will respect the possible wildcard `*` and add all `->logFillable()` or `->logUnguarded()` methods.

## Basic example of what is logged

Let's see what gets logged when creating an instance of that model.

```php
$newsItem = NewsItem::create([
   'name' => 'original name',
   'text' => 'Lorum'
]);

//creating the newsItem will cause an activity being logged
$activity = Activity::all()->last();

$activity->description; //returns 'created'
$activity->subject; //returns the instance of NewsItem that was created
$activity->changes; //returns ['attributes' => ['name' => 'original name', 'text' => 'Lorum']];
```

Now let's update some that `$newsItem`.

```php
$newsItem->name = 'updated name';
$newsItem->save();

//updating the newsItem will cause an activity being logged
$activity = Activity::all()->last();

$activity->description; //returns 'updated'
$activity->subject; //returns the instance of NewsItem that was created
```

Calling `$activity->changes` will return this array:

```php
[
   'attributes' => [
        'name' => 'updated name',
        'text' => 'Lorum',
    ],
    'old' => [
        'name' => 'original name',
        'text' => 'Lorum',
    ],
];
```

Pretty Zonda, right?

Now, what happens when you call delete?

```php
$newsItem->delete();

//deleting the newsItem will cause an activity being logged
$activity = Activity::all()->last();

$activity->description; //returns 'deleted'
$activity->changes; //returns ['attributes' => ['name' => 'updated name', 'text' => 'Lorum']];
```

## Customizing the events being logged

By default the package will log the `created`, `updated`, `deleted` events. You can modify this behaviour by setting the `$recordEvents` property on a model.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class NewsItem extends Model
{
    use LogsActivity;

    //only the `deleted` event will get logged automatically
    protected static $recordEvents = ['deleted'];
}
```

## Customizing the description

By default the package will log `created`, `updated`, `deleted` in the description of the activity. You can modify this text by providing callback to the `->setDescriptionForEvent()` method on `LogOptions` class.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
   'text' => 'Lorum'
]);

//creating the newsItem will cause an activity being logged
$activity = Activity::all()->last();

$activity->description; //returns 'This model has been created'
```

## Customizing the log name

Specify name by provide string to `->useLogName()` to make the model use another name than the default.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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

## Ignoring changes to certain attributes

If your model contains attributes whose change don't need to trigger an activity being logged you can use `->dontLogIfAttributesChangedOnly()`

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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

Changing `text` will not trigger an activity being logged.

By default the `updated_at` attribute is _not_ ignored and will trigger an activity being logged. You can add the `updated_at` attribute to the `->dontLogIfAttributesChangedOnly()` array to override this behavior.

## Logging only the changed attributes

If you do not want to log every attribute passed into `->logOnly()`, but only those that have actually changed after the update, you can call `->logOnlyDirty()`.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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

If you would like to log only the changes to a specific JSON objects sub-keys. You can use the same method for logging specific columns with the difference of choosing the json key to log.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

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

Changing only `preferences->notifications->status` or `preferences->hero_url` means only the `preferences->notifications->status` or `preferences->hero_url` attribute will be logged in the activity, and everything else `preferences` will be left out.

The output of this in a activity entry would be as follows:

```php
// Create a news item.
$newsItem = NewsItem::create([
    'name' => 'Title',
    'preferences' => [
        'notifications' => [
            'status' => 'on',
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

$lastActivity->properties->toArray();
```

```php
// output
[
    "attributes" => [
        "preferences" => [ // the updated values
            "notifications" => [
                "status" => "on",
            ],
            "hero_url" => "http://example.com/hero.png",
        ],
    ],
    "old" => [
        "preferences" => [ // the old settings
            "notifications" => [
                "status" => "off",
            ],
            "hero_url" => "",
        ],
    ],
]
```

The result in the log entry key for the attribute will be what is in the `->logOnly()`.

## Prevent save logs items that have no changed attribute

Calling `->dontSubmitEmptyLogs()` prevents the package from storing empty logs. Storing empty logs can happen when you only want to log a certain attribute but only another changes.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'text'];

   public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['text'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

}
```

## Ignoring attributes from logging

If you use wildcard logging, but do not want to log certain attributes, you can specify those attributes by calling `->dontLogIfAttributesChangedOnly()`.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class NewsItem extends Model
{
    use LogsActivity;

   public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logAll()
        ->dontLogIfAttributesChangedOnly(['text'])
        ->logOnlyDirty();
    }

}
```

Even if there are changes to `text` attribute, they will not be logged.

## Using the CausesActivity trait

The package ships with a `CausesActivity` trait which can be added to any model that you use as a causer. It provides an `actions` relationship which returns all activities that are caused by the model.

If you include it in the `User` model you can simply retrieve all the current users activities like this:

```php

\Auth::user()->actions;

```

## Disabling logging on demand

You can also disable logging for a specific model at runtime. To do so, you can use the `disableLogging()` method:

```php
$newsItem = NewsItem::create([
   'name' => 'original name',
   'text' => 'Lorum'
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
   'text' => 'Lorum'
]);

// Updating with logging disabled
$newsItem->disableLogging();

$newsItem->update(['name' => 'The new name is not logged']);

// Updating with logging enabled
$newsItem->enableLogging();

$newsItem->update(['name' => 'The new name is logged']);
```

## Tap Activity before logged from event

In addition to the `tap()` method on `ActivityLogger` you can utilise the `tapActivity()` method in your observed model class. This method will allow you to fill properties and add custom fields before the activity is saved.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class NewsItem extends Model
{
    use LogsActivity;

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->description = "activity.logs.message.{$eventName}";
    }
}
```

## Logging on Pivot Models

Sometimes you want to log changes on your pivot model - for example if it contains additional data.
By default pivot models don't have a primary key/column and because of this can't be used in eloquent relations.
To solve this you have to add a primary key column `id` to your pivot table (`$table->id('id')`) and configure your pivot model to use this primary key.

```php
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\Traits\LogsActivity;

final class PivotModel extends Pivot
{
    use LogsActivity;

    public $incrementing = true;
}
```

After these changes you can log activities on your pivot models as expected.
