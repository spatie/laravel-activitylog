---
title: Logging activity
weight: 1
---

## Description

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

You can specify on which object the activity is performed by using `performedOn`:

```php
activity()
   ->performedOn($someContentModel)
   ->log('edited');

$lastActivity = Activity::all()->last(); //returns the last logged activity

$lastActivity->subject; //returns the model that was passed to `performedOn`;
```

The `performedOn`-function has a shorter alias name: `on`

## Setting a causer

You can set who or what caused the activity by using `causedBy`:

```php
activity()
   ->causedBy($userModel)
   ->performedOn($someContentModel)
   ->log('edited');
   
$lastActivity = Activity::all()->last(); //returns the last logged activity

$lastActivity->causer; //returns the model that was passed to `causedBy`;   
```

The `causedBy()`-function has a shorter alias named: `by`

If you're not using `causedBy` the package will automatically use the logged in user.

If you don't want to associate a model as causer of activity, you can use `causedByAnonynmous` (or the shorter alias: `byAnonymous`).

## Setting custom properties

You can add any property you want to an activity by using `withProperties`

```php
activity()
   ->causedBy($userModel)
   ->performedOn($someContentModel)
   ->withProperties(['key' => 'value'])
   ->log('edited');
   
$lastActivity = Activity::all()->last(); //returns the last logged activity
   
$lastActivity->getExtraProperty('key'); //returns 'value' 

$lastActivity->where('properties->key', 'value')->get(); // get all activity where the `key` custom property is 'value'
```

## Setting custom created date

You can set a custom activity created_at date time by using `createdAt` or `at`

```php
activity()
    ->causedBy($userModel)
    ->performedOn($someContentModel)
    ->createdAt(now()->subDays(10))
    ->log('created')
```

## Tap Activity before logged

You can use the `tap()` method to fill properties and add custom fields before the activity is saved.

```php
use Spatie\Activitylog\Contracts\Activity;

activity()
   ->causedBy($userModel)
   ->performedOn($someContentModel)
   ->tap(function(Activity $activity) {
      $activity->my_custom_field = 'my special value';
   })
   ->log('edited');
   
$lastActivity = Activity::all()->last();

$lastActivity->my_custom_field; // returns 'my special value'
```
