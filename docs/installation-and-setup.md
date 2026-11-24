---
title: Installation and Setup
weight: 4
---

The package can be installed via composer:

```bash
composer require vsrklabs/laravel-mongo-activitylog
```

The package will automatically register the service provider.

Configure a `mongodb` connection in `config/database.php` and make it the default connection, or use a custom Activity model that sets `protected $connection = 'mongodb';`. Models logged by this package should extend `MongoDB\Laravel\Eloquent\Model`.

You can publish the migration with:

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
```

**Note:** The default migration assumes you are using integers for your model IDs. If you are using UUIDs, or some other format, adjust the format of the `subject_id` and `causer_id` fields in the published migration before continuing.

After the migration has been published, you can create the `activity_log` collection by running the migrations:

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
     * and extend MongoDB\Laravel\Eloquent\Model.
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

Without buffering, activities on the same database connection as their subject are rolled back together.

When using a different connection or [buffering activities](/docs/laravel-activitylog/v5/advanced-usage/buffering), the package waits until the subject's transaction commits. If it rolls back, the activities are discarded. These activities have no ID until they are saved, after commit or the next buffer flush.

Writes to separate databases are not atomic: if saving an activity fails after commit, the subject's changes remain saved. MongoDB multi-document transactions require a replica set.
