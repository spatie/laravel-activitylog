---
title: Using multiple logs
weight: 5
---

## The default log

Without specifying a log name, the activities will be logged on the default log.

```php
activity()->log('hi');

$lastActivity = Activity::all()->last();

$lastActivity->log_name; //returns 'default';
```

You can specify the name of the default log in the `default_log_name` key of the config file.

## Specifying a log

You can specify the log on which an activity must be logged by passing the log name to the `activity` function:

```php
activity('other-log')->log("hi");

Activity::all()->last()->log_name; //returns 'other-log';
```

## Specifying a log for each model

By default, the `LogsActivity` trait uses `default_log_name` from the config file to write the logs. To customize the log's name for each model, call the `useLogName()` method when configuring the `LogOptions`.

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->useLogName('custom_log_name_for_this_model');
}
```

## Using a backed enum as log name

Everywhere a log name is accepted, you can also pass a backed enum. Its `value` is what gets stored
in the `log_name` column.

```php
enum LogName: string
{
    case Orders = 'orders';
    case Auth = 'auth';
}

activity(LogName::Orders)->log('hi');

Activity::all()->last()->log_name; //returns 'orders'
```

This works in `useLog()` and `inLog()` on the logger, and in `useLogName()` on `LogOptions`.

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->useLogName(LogName::Orders);
}
```

Because `log_name` is a string column, it's always read back as a string (an int-backed enum reads
back as a numeric string). If you want it hydrated into an enum, add the cast on your own activity
model and register it in the `activity_model` key of the config file:

```php
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'log_name' => LogName::class,
        ];
    }
}
```

## Retrieving activity

The `Activity` model is just a regular Eloquent model that you know and love:

```php
Activity::where('log_name', 'other-log')->get(); //returns all activity from the 'other-log'
```

There's also an `inLog` scope you can use:

```php
Activity::inLog('other-log')->get();

//you can pass multiple log names to the scope
Activity::inLog('default', 'other-log')->get();

//passing an array is just as good
Activity::inLog(['default', 'other-log'])->get();

//backed enums work here too
Activity::inLog(LogName::Orders)->get();
Activity::inLog(LogName::Orders, LogName::Auth)->get();
```
