---
title: Customizing actions
weight: 7
---

The package uses action classes for its core operations. You can extend these to customize behavior.

## Available actions

### LogActivityAction

Called every time an activity is logged. Handles description placeholder replacement, calling `beforeActivityLogged()` on the subject, and saving the activity to the database.

### CleanActivityLogAction

Called by the `activitylog:clean` command. Handles deleting old activity records.

## Overriding an action

Create a class that extends the original action and override the protected methods you want to customize:

```php
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Contracts\Activity;

class CustomLogActivityAction extends LogActivityAction
{
    protected function save(Activity $activity): void
    {
        // Example: dispatch to queue instead of saving synchronously
        dispatch(fn () => $activity->save());
    }
}
```

Then register it in the config:

```php
// config/activitylog.php
'actions' => [
    'log_activity' => \App\Actions\CustomLogActivityAction::class,
    'clean_log' => \Spatie\Activitylog\Actions\CleanActivityLogAction::class,
],
```

## Overridable methods

### LogActivityAction

| Method | Description |
|--------|-------------|
| `resolveDescription($activity, $description)` | Resolves placeholders like `:subject.name` in the description |
| `beforeActivityLogged($activity)` | Calls `beforeActivityLogged()` on the subject model if it exists |
| `save($activity)` | Saves the activity to the database |
| `replacePlaceholders($description, $activity)` | Performs the actual placeholder replacement |

### CleanActivityLogAction

| Method | Description |
|--------|-------------|
| `getCutOffDate($maxAgeInDays)` | Calculates the date before which records should be deleted |
| `deleteOldActivities($cutOffDate, $logName)` | Performs the actual deletion query |
