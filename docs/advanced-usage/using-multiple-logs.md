---
title: Using multiple logs
weight: 5
---

## The default log

Without specifying a log name the activities will be logged on the default log.

```php
activity()->log('hi');

$lastActivity = Spatie\Activitylog\Models\Activity::all()->last();

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

By default, the `LogsActivity` trait uses `default_log_name` from the config file to write the logs. To customize the log's name for each model, call the useLogName() method when configuring the LogOptions.

```
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->useLogName('custom_log_name_for_this_model');
}
```

## Retrieving activity

The `Activity` model is just a regular Eloquent model that you know and love:

```php
Activity::where('log_name' , 'other-log')->get(); //returns all activity from the 'other-log'
```

There's also an `inLog` scope you can use:

```php
Activity::inLog('other-log')->get();

//you can pass multiple log names to the scope
Activity::inLog('default', 'other-log')->get();

//passing an array is just as good
Activity::inLog(['default', 'other-log'])->get();
```
