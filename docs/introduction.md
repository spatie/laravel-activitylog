---
title: Introduction
weight: 1
---

The `spatie/laravel-activitylog` package provides easy to use functions to log the activities of the users of your app. It can also automatically log model events. All activity will be stored in the `activity_log` table.

Here's a litte demo of how you can use it:

```php
activity()->log('Look mum, I logged something');
```

You can retrieve all activity using the `Spatie\Activitylog\Models\Activity` model.

```php
Activity::all();
```

Here's a more advanced example:

```php
activity()
   ->performedOn($anEloquentModel)
   ->causedBy($user)
   ->withProperties(['customProperty' => 'customValue'])
   ->log('Look mum, I logged something');

$lastLoggedActivity = Activity::all()->last();

$lastLoggedActivity->subject; //returns an instance of an eloquent model
$lastLoggedActivity->causer; //returns an instance of your user model
$lastLoggedActivity->getExtraProperty('customProperty'); //returns 'customValue'
$lastLoggedActivity->description; //returns 'Look mum, I logged something'
```

Here's an example on [event logging](/docs/laravel-activitylog/v4/advanced-usage/logging-model-events).

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
   'old' => [
        'name' => 'original name',
        'text' => 'Lorum',
    ],
    'attributes' => [
        'name' => 'updated name',
        'text' => 'Lorum',
    ],
];
```

## We have badges!

<section class="article_badges">
    <a href="https://packagist.org/packages/spatie/laravel-activitylog"><img src="https://img.shields.io/badge/packagist-spatie/laravel-activitylog.svg?style=flat-square" alt="spatie/laravel-activitylog"></a>
    <a href="https://packagist.org/packages/spatie/laravel-activitylog"><img src="https://img.shields.io/packagist/v/spatie/laravel-activitylog.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/spatie/laravel-activitylog/blob/master/LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="Software License"></a>
    <a href="https://travis-ci.org/spatie/laravel-activitylog"><img src="https://img.shields.io/travis/spatie/laravel-activitylog/master.svg?style=flat-square" alt="Build Status"></a>
    <a href="https://scrutinizer-ci.com/g/spatie/laravel-activitylog"><img src="https://img.shields.io/scrutinizer/g/spatie/laravel-activitylog.svg?style=flat-square" alt="Quality Score"></a>
    <a href="https://packagist.org/packages/spatie/laravel-activitylog"><img src="https://img.shields.io/packagist/dt/spatie/laravel-activitylog.svg?style=flat-square" alt="Total Downloads"></a>
</section>
