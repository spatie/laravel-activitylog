---
title: Installation and Setup
weight: 4
---

The package can be installed via composer:

```bash
composer require spatie/laravel-activitylog
```

The package will automatically register the service provider.

You can publish the migration with:

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
```

**Note:** The default migration assumes you are using integers for your model IDs. If you are using UUIDs, or some other format, adjust the format of the `subject_id` and `causer_id` fields in the published migration before continuing.

After the migration has been published, you can create the `activity_log` table by running the migrations:

```bash
php artisan migrate
```

You can optionally publish the config file with:

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
```

This is the contents of the published config file:

```php
return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITYLOG_ENABLED', true),

    /*
     * When the clean command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'clean_after_days' => 365,

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => 'default',

    /*
     * You can specify an auth driver here that gets user models.
     * If this is null we'll use the current Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject relationship on activities
     * will include soft deleted models.
     */
    'include_soft_deleted_subjects' => false,

    /*
     * This model will be used to log activity.
     * It should implement the Spatie\Activitylog\Contracts\Activity interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    /*
     * These attributes will be excluded from logging for all models.
     * Model-specific exclusions via logExcept() are merged with these.
     */
    'default_except_attributes' => [],

    /*
     * These action classes can be overridden to customize how activities
     * are logged and cleaned. Your custom classes must extend the originals.
     */
    'actions' => [
        'log_activity' => \Spatie\Activitylog\Actions\LogActivityAction::class,
        'clean_log' => \Spatie\Activitylog\Actions\CleanActivityLogAction::class,
    ],
];
```

## Custom table name or database connection

If you need to use a custom table name or database connection, create a custom Activity model that extends the default one:

```php
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    protected $table = 'my_custom_activity_log';

    protected $connection = 'my_custom_connection';
}
```

Then set it in the config:

```php
'activity_model' => \App\Models\Activity::class,
```

### Database transactions

When the activity and its subject use the same database connection, an unbuffered activity is saved immediately within the subject's transaction. Rolling back that transaction also rolls back the activity.

When they use different connections, the package waits until the subject's outermost transaction commits before saving the activity. Rolling back the transaction discards its activities. Rolling back a nested savepoint discards only the activities belonging to that savepoint and its descendants. Activities that survive are saved in their original logging order.

The description, changes, properties, and timestamps are captured when you log the activity, after the logging hooks have run. Without buffering, the model returned by `log()` has no ID until the transaction commits. With buffering, the activity is added to the buffer after commit and inserted when the buffer is flushed.

Activities without a subject, and activities whose subject is not in a transaction, keep their usual behavior. The package does not infer a source transaction from the causer or from unrelated database connections. This behavior applies to the logging API and automatic model event logging; directly saving an activity model bypasses the logging action.

This does not make writes to two databases atomic. If saving the activity fails after the subject's transaction has committed, the subject's changes remain committed. Applications that require guaranteed delivery should use a durable outbox or another retry mechanism.
