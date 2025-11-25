## Laravel Activity Log

This package provides automatic and manual activity logging for Laravel applications. It tracks model changes, user actions, and custom events with detailed change history stored in the `activity_log` table.

### Installation

After installing via Composer, publish and run migrations:

@verbatim
<code-snippet name="Publish and migrate" lang="bash">
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
</code-snippet>
@endverbatim

### Basic Logging

- **Simple Log**: Log any activity with a description.
- **With Context**: Add causer (user), subject (model), and custom properties.

@verbatim
<code-snippet name="Basic activity logging" lang="php">
// Simple log
activity()->log('User viewed dashboard');

// With context
activity()
    ->causedBy($user)
    ->performedOn($model)
    ->withProperties(['ip' => request()->ip()])
    ->log('Updated settings');
</code-snippet>
@endverbatim

### Automatic Model Event Logging

Add `LogsActivity` trait to models to auto-log creates, updates, and deletes:

@verbatim
<code-snippet name="Enable automatic logging on model" lang="php">
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Article extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'content', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
</code-snippet>
@endverbatim

### Log Options Configuration

- **logOnly(['field'])**: Only log specific attributes
- **logAll()**: Log all model attributes
- **logOnlyDirty()**: Log only changed attributes
- **dontLogIfAttributesChangedOnly(['updated_at'])**: Ignore specific fields
- **useLogName('custom')**: Use custom log name
- **setDescriptionForEvent(fn($event) => "Model was {$event}")**: Custom descriptions

@verbatim
<code-snippet name="Advanced log options" lang="php">
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logAll()
        ->logExcept(['password', 'remember_token'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->useLogName('users')
        ->setDescriptionForEvent(fn(string $event) => "User account was {$event}");
}
</code-snippet>
@endverbatim

### Retrieving Activity Logs

Query activities using the `Activity` model:

@verbatim
<code-snippet name="Query activity logs" lang="php">
use Spatie\Activitylog\Models\Activity;

// Get all activities
$activities = Activity::all();

// Get last activity
$lastActivity = Activity::latest()->first();

// Access log data
$lastActivity->description;        // 'updated'
$lastActivity->subject;            // Related model instance
$lastActivity->causer;             // User who performed action
$lastActivity->properties;         // Custom properties
$lastActivity->changes();          // ['attributes' => [...], 'old' => [...]]

// Query by subject
Activity::forSubject($model)->get();

// Query by causer
Activity::causedBy($user)->get();

// Query by log name
Activity::inLog('users')->get();
</code-snippet>
@endverbatim

### Custom Properties

Add custom data to any activity log:

@verbatim
<code-snippet name="Custom properties" lang="php">
activity()
    ->performedOn($order)
    ->withProperties([
        'old_status' => 'pending',
        'new_status' => 'completed',
        'total_amount' => 199.99,
        'ip_address' => request()->ip()
    ])
    ->log('Order status changed');

// Retrieve custom properties
$activity->getExtraProperty('old_status');
</code-snippet>
@endverbatim

### Batching Activities

Group related activities together:

@verbatim
<code-snippet name="Batch logging" lang="php">
use Spatie\Activitylog\Facades\LogBatch;

LogBatch::startBatch();

activity()->log('First action');
activity()->log('Second action');
activity()->log('Third action');

LogBatch::endBatch();

// All three activities share the same batch_uuid
$batchUuid = LogBatch::getUuid();
Activity::where('batch_uuid', $batchUuid)->get();
</code-snippet>
@endverbatim

### Configuration Options

Publish config file for customization:

@verbatim
<code-snippet name="Publish config" lang="bash">
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
</code-snippet>
@endverbatim

Key config options in `config/activitylog.php`:

- **enabled**: Toggle logging on/off (useful for testing)
- **delete_records_older_than_days**: Auto-cleanup threshold (default: 365)
- **default_log_name**: Default log category name
- **activity_model**: Custom Activity model class
- **table_name**: Custom table name
- **subject_returns_soft_deleted_models**: Include soft-deleted subjects

### Cleaning Old Logs

Remove old activity records:

@verbatim
<code-snippet name="Clean old logs" lang="bash">
# Delete logs older than configured days
php artisan activitylog:clean

# Specify custom retention period
php artisan activitylog:clean --days=90
</code-snippet>
@endverbatim

### Common Use Cases

@verbatim
<code-snippet name="User authentication logging" lang="php">
// In LoginController
activity()
    ->causedBy($user)
    ->withProperties(['ip' => request()->ip()])
    ->log('User logged in');
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Admin action audit trail" lang="php">
// Track admin changes
activity()
    ->causedBy(auth()->user())
    ->performedOn($user)
    ->withProperties(['role_changed' => 'admin to editor'])
    ->log('User role modified');
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="API request logging" lang="php">
// In API middleware
activity()
    ->causedBy($user)
    ->withProperties([
        'endpoint' => $request->path(),
        'method' => $request->method(),
        'ip' => $request->ip()
    ])
    ->log('API request');
</code-snippet>
@endverbatim

### Custom Activity Model

Extend the default Activity model for custom behavior:

@verbatim
<code-snippet name="Custom Activity model" lang="php">
namespace App\Models;

use Spatie\Activitylog\Models\Activity as BaseActivity;

class CustomActivity extends BaseActivity
{
    public function getHumanReadableDateAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}

// In config/activitylog.php
'activity_model' => \App\Models\CustomActivity::class,
</code-snippet>
@endverbatim

### Disabling Logging Temporarily

Use `LogBatch` to disable logging for specific operations:

@verbatim
<code-snippet name="Disable logging temporarily" lang="php">
use Spatie\Activitylog\Facades\LogBatch;

LogBatch::withholdBatch(function() {
    // Activities here won't be logged
    $user->update(['name' => 'New Name']);
});
</code-snippet>
@endverbatim

### Testing

Disable activity logging in tests:

@verbatim
<code-snippet name="Disable in tests" lang="php">
// In phpunit.xml or .env.testing
ACTIVITY_LOGGER_ENABLED=false

// Or in test setup
config(['activitylog.enabled' => false]);
</code-snippet>
@endverbatim

### Best Practices

1. **Use `logOnlyDirty()`** to avoid logging when nothing changed
2. **Use `logOnly()`** to avoid storing sensitive data (passwords, tokens)
3. **Set log names** to categorize different types of activities
4. **Clean old logs regularly** using scheduled command
5. **Index `subject_type`, `subject_id`, `causer_id`** for better query performance
6. **Use batching** for bulk operations to group related changes
