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

                if ($description == '') {
                    return;
                }

                app(ActivityLogger::class)
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
