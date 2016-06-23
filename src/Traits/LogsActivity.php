<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\ActivityLogger;

trait LogsActivity
{
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
                    $extraProperties['changes'] = $model->getChanges();
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

    /*
     * Get the fieldnames and their values that have been changed.
     */
    public function getChanges(): array
    {
        return array_intersect_key($this->fresh()->toArray(), $this->getDirty());
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
            'deleting',
            'deleted',
        ];
    }
}
