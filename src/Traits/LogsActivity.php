<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Models\Activity;

trait LogsActivity
{
    use DetectsChanges;

    protected static function bootLogsActivity()
    {
        static::eventsToBeRecorded()->each(function ($eventName) {

            return static::$eventName(function (Model $model) use ($eventName) {

                $description = $model->getDescriptionForEvent($eventName);

                $logName = $model->getLogToUse();

                if ($description == '') {
                    return;
                }

                app(ActivityLogger::class)
                    ->useLog($logName)
                    ->performedOn($model)
                    ->withProperties($model->attributeValuesToBeLogged($eventName))
                    ->log($description);
            });

        });
    }

    public function activity(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return $eventName;
    }

    public function getLogToUse(): string
    {
        return config('laravel-activitylog.default_log_name');
    }

    /*
     * Get the event names that should be recorded.
     */
    protected static function eventsToBeRecorded(): Collection
    {
        if (isset(static::$recordEvents)) {
            return collect(static::$recordEvents);
        }

        return collect([
            'created',
            'updated',
            'deleted',
        ]);
    }
}
