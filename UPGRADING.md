## From v3 to v4

``` bash
composer require spatie/laravel-activitylog "^4.0.0"
```

### Publish migrations & migrate new tables

``` bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
```

### Model event Logging

- All models now need to define a `getActivitylogOptions()` method to configure and return the models options as a `LogOptions` instance.
- To control what attributes are logged, instead of defining a `$logAttributes` property this is defined in the `getActivitylogOptions()` method using the `logOnly()` method of `LogOptions`.
- The `getDescriptionForEvent()` method is no longer used to customize the description. Instead, use the `setDescriptionForEvent()` method for `LogOptions` class.
- When customizing the log's name instead of defining a `$logName` property, call the `useLogName()` method when configuring the `LogOptions`.
- Instead of the `$ignoreChangedAttributes` property the ` dontLogIfAttributesChangedOnly()` method should be used.
- If you only need to log the dirty attributes use `logOnlyDirty()` since the `$logOnlyDirty` property is no longer used.
- For instances where you do not want to store empty log events use `dontSubmitEmptyLogs()` instead of setting `$submitEmptyLogs` to `false`.
- When you use a `*` (wildcard) and want to ignore specific elements use the `dontLogIfAttributesChangedOnly()` method instead of the `$logAttributesToIgnore` property.

## From v2 to v3

- if you are using a custom `Activity` model, you should let it implement the new `Spatie\Activitylog\Contracts\Activity` interface
- the preferred way to get changes on an `Activity` model is through the `changes` property instead of the `changes()` function. Change all usages from
`$activity->changes()` to `$activity->changes`
- the `activity` relation of the `CausesActivity` trait has been renamed to `actions`.  Rename all uses from `$user->activity` to `$user->actions`
- the `activity` relation of the `LogsActivity` trait has been renamed to `activities`. Rename all uses from `$yourModel->activity` to `$yourModel->activities`.
- the deprecated `loggedActivity` relation has been removed. Use `activities` instead.
- the `HasActivity` trait has been removed. Use both `CausesActivity` and `LogsActivity` traits instead.
