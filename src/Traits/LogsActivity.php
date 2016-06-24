<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\ActivityLogger;

trait LogsActivity
{
    use DetectsChanges;

    protected static function bootLogsActivity()
    {
        collect(static::eventsToBeRecorded())->each(function ($eventName) {

            return static::$eventName(function (Model $model) use ($eventName) {

                $description = $model->getDescriptionForEvent($eventName);

                if ($description == '') {
                    return;
                }

                $extraProperties = [];
                if ($eventName != 'deleted') {
                    $extraProperties['changes'] = $model->getChangedValues();
                }

                app(ActivityLogger::class)
                    ->performedOn($model)
                    ->withExtraProperties($extraProperties)
                    ->log($description);
            });

        });
    }

    public function causesActivity(): MorphTo
    {
        return $this->morphTo();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return $eventName;
    }

    /*
     * Get the event names that should be recorded.
     */
    protected static function eventsToBeRecorded(): array
    {
        if (isset(static::$recordEvents)) {
            return static::$recordEvents;
        }

        return [
            'created',
            'updated',
            'deleted',
        ];
    }
}
