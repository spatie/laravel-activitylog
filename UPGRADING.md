## From v4 to v5

```bash
composer require spatie/laravel-activitylog "^5.0.0"
```

### Requirements

v5 requires **PHP 8.4+** and **Laravel 12+**. If you're on older versions, stay on v4.

### Migration

v5 ships a single consolidated migration with a new schema. If you're upgrading from v4, you need to create a migration to:

1. Add the `attribute_changes` column (new, stores tracked model changes)
2. Drop the `batch_uuid` column (batch system removed)
3. Migrate existing change data from `properties` to `attribute_changes`

```php
// Example upgrade migration
Schema::table('activity_log', function (Blueprint $table) {
    $table->json('attribute_changes')->nullable()->after('causer_id');
    $table->dropColumn('batch_uuid');
});

// Then migrate existing data: move 'attributes' and 'old' from properties to attribute_changes
DB::table('activity_log')->whereNotNull('properties')->eachById(function ($row) {
    $properties = json_decode($row->properties, true);
    $changes = array_intersect_key($properties, array_flip(['attributes', 'old']));
    $remaining = array_diff_key($properties, array_flip(['attributes', 'old']));

    DB::table('activity_log')->where('id', $row->id)->update([
        'attribute_changes' => empty($changes) ? null : json_encode($changes),
        'properties' => empty($remaining) ? null : json_encode($remaining),
    ]);
});
```

### Schema changes

- **`properties`** column now stores only custom user data (set via `withProperties()`)
- **`attribute_changes`** column (new) stores tracked model changes (`attributes`, `old`)
- **`batch_uuid`** column removed (batch system dropped)
- `getExtraProperty()` renamed to `getProperty()`
- `$activity->changes` / `$activity->changes()` replaced by `$activity->attribute_changes`

### Batch system removed

The `LogBatch` class, `LogBatch` facade, and `Activity::batch()` have been removed. If you need to group activities, use a custom property:

```php
$groupId = Str::uuid();
activity()->withProperty('group', $groupId)->log('first');
Activity::where('properties->group', $groupId)->get();
```

### Renamed methods

| v4 | v5 |
|---|---|
| `$model->activities` (LogsActivity) | `$model->activitiesAsSubject` |
| `$model->actions` (CausesActivity) | `$model->activitiesAsCauser` |
| `$activity->changes()` | `$activity->attribute_changes` |
| `getExtraProperty()` | `getProperty()` |
| `dontSubmitEmptyLogs()` | `dontLogEmptyChanges()` |
| `submitEmptyLogs()` | `logEmptyChanges()` |
| `withoutLogs()` | `withoutLogging()` |
| `CauserResolver::setCauser($model)` | `Activity::defaultCauser($model)` |
| `CauserResolver::withCauser($model, fn)` | `Activity::defaultCauser($model, fn)` |
| `tapActivity($activity, $event)` on models | `beforeActivityLogged($activity, $event)` |

### Namespace changes

Several classes moved to sub-namespaces:

| v4 | v5 |
|---|---|
| `Spatie\Activitylog\LogOptions` | `Spatie\Activitylog\Support\LogOptions` |
| `Spatie\Activitylog\CauserResolver` | `Spatie\Activitylog\Support\CauserResolver` |
| `Spatie\Activitylog\ActivityLogStatus` | `Spatie\Activitylog\Support\ActivityLogStatus` |
| `Spatie\Activitylog\ActivityLogger` | `Spatie\Activitylog\Support\ActivityLogger` |
| `Spatie\Activitylog\PendingActivityLog` | `Spatie\Activitylog\Support\PendingActivityLog` |
| `Spatie\Activitylog\ActivityEvent` | `Spatie\Activitylog\Enums\ActivityEvent` |
| `Spatie\Activitylog\CleanActivitylogCommand` | `Spatie\Activitylog\Commands\CleanActivitylogCommand` |
| `Spatie\Activitylog\Traits\LogsActivity` | `Spatie\Activitylog\Models\Concerns\LogsActivity` |
| `Spatie\Activitylog\Traits\CausesActivity` | `Spatie\Activitylog\Models\Concerns\CausesActivity` |
| `Spatie\Activitylog\Traits\HasActivity` | `Spatie\Activitylog\Models\Concerns\HasActivity` |

### HasActivity trait

v5 reintroduces the `HasActivity` trait. It combines `LogsActivity` and `CausesActivity` and provides an `activities()` convenience method. Use it on models (like User) that both cause and log activities.

### getActivitylogOptions() is now optional

You no longer need to implement `getActivitylogOptions()` on every model. The default logs events (created, updated, deleted) without tracking attribute changes. Override the method only when you need to customize attribute logging.

### CauserResolver facade removed

The `CauserResolver` facade has been removed. Use `Activity::defaultCauser()` instead:

```php
// v4
CauserResolver::setCauser($admin);
CauserResolver::withCauser($admin, fn() => ...);

// v5
Activity::defaultCauser($admin);
Activity::defaultCauser($admin, fn() => ...);
```

The `CauserResolver` class still exists and can be resolved from the container for advanced use cases (custom resolution callbacks).

### ActivityEvent enum

v5 introduces an `ActivityEvent` enum. All methods that accept event names also accept the enum:

```php
use Spatie\Activitylog\Enums\ActivityEvent;

Activity::forEvent(ActivityEvent::Created)->get();
activity()->event(ActivityEvent::Updated)->log('...');
```

Plain strings still work for custom event names.

### Config changes

The config file has been simplified. Republish it or update manually.

**Renamed keys:**

| v4 | v5 |
|---|---|
| `enabled` (env: `ACTIVITY_LOGGER_ENABLED`) | `enabled` (env: `ACTIVITYLOG_ENABLED`) |
| `delete_records_older_than_days` | `clean_after_days` |
| `subject_returns_soft_deleted_models` | `include_soft_deleted_subjects` |

**Removed keys:**
- `table_name` and `database_connection` have been removed. If you need a custom table name or connection, create a custom Activity model and set `$table` / `$connection` on it. Then point `activity_model` to your custom model.

**New keys:**
- `actions.log_activity`: action class for logging (default: `LogActivityAction::class`)
- `actions.clean_log`: action class for cleaning (default: `CleanActivityLogAction::class`)

### Pipe system removed

The `addLogChange()` method, `LoggablePipe` interface, and `EventLogBag` class have been removed. To manipulate the changes array before saving, override `transformChanges()` on a custom `LogActivityAction` instead. See the [customizing actions](/docs/laravel-activitylog/v5/advanced-usage/customizing-actions) documentation.

### Customizable actions

Core operations are now handled by action classes that can be extended and swapped via config. This lets you customize how activities are saved (e.g., queue them) or how old records are cleaned without overriding the entire logger or command.

**New keys:**
- `default_except_attributes`: globally exclude attributes from logging for all models

---

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
- Instead of the `$ignoreChangedAttributes` property the `dontLogIfAttributesChangedOnly()` method should be used.
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
