---
title: Logging activity
weight: 1
---

This is the most basic way to log activity:

```php
activity()->log('Look mum, I logged something');
```

You can retrieve the activity using the `Spatie\Activitylog\Models\Activity` model.

```php
$lastActivity = Activity::all()->last(); //returns the last logged activity

$lastActivity->description; //returns 'Look mum, I logged something';
```

## Setting a subject

You can specify on which object the activity is performed by using `performedOn()`:

```php
activity()
   ->performedOn($someContentModel)
   ->log('edited');

$lastActivity = Activity::all()->last(); //returns the last logged activity

$lastActivity->subject; //returns the model that was passed to `performedOn`;
```

The `performedOn()` method has a shorter alias: `on()`

## Setting a causer

You can set who or what caused the activity by using `causedBy()`:

```php
activity()
   ->causedBy($userModel)
   ->performedOn($someContentModel)
   ->log('edited');

$lastActivity = Activity::all()->last(); //returns the last logged activity

$lastActivity->causer; //returns the model that was passed to `causedBy`;
```

The `causedBy()` method has a shorter alias: `by()`

If you're not using `causedBy()`, the package will automatically use the logged in user.

If you don't want to associate a model as causer of activity, you can use `causedByAnonymous()` (or the shorter alias: `byAnonymous()`).

## Setting custom properties

You can add arbitrary metadata to an activity by using `withProperties()`. This is separate from `attribute_changes`, which the package uses to store old/new model attribute values when [logging model events](/docs/laravel-activitylog/v5/advanced-usage/logging-model-events).

```php
activity()
   ->causedBy($userModel)
   ->performedOn($someContentModel)
   ->withProperties(['key' => 'value'])
   ->log('edited');

$lastActivity = Activity::all()->last(); //returns the last logged activity

$lastActivity->getProperty('key'); //returns 'value'

Activity::where('properties->key', 'value')->get(); // get all activity where the `key` custom property is 'value'
```

## Setting custom created date

You can set a custom activity `created_at` date time by using `createdAt()`

```php
activity()
    ->causedBy($userModel)
    ->performedOn($someContentModel)
    ->createdAt(now()->subDays(10))
    ->log('created');
```

## Setting custom event

You can set a custom activity `event` by using `event()`

```php
activity()
    ->causedBy($userModel)
    ->performedOn($someContentModel)
    ->event('verified')
    ->log('The user has verified the content model.');
```

## Tap Activity before logged

You can use the `tap()` method to fill properties and add custom fields before the activity is saved.

```php
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

activity()
   ->causedBy($userModel)
   ->performedOn($someContentModel)
   ->tap(function(ActivityContract $activity) {
      $activity->my_custom_field = 'my special value';
   })
   ->log('edited');

$lastActivity = Activity::all()->last();

$lastActivity->my_custom_field; // returns 'my special value'
```
