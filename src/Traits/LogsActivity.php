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

                if ($eventName != 'restored' && $eventName != 'deleted' && (! count(array_except($model->getDirty(), $model->attributesToBeIgnored())))) {
                    return;
                }

                if($eventName == 'updated' && array_has($model->getDirty(), 'deleted_at')) {
                    if($model->getDirty()['deleted_at'] === null) return;
                }

                if($model->forceDeleting) $eventName = 'force-deleted';

                $description = $model->getDescriptionForEvent($eventName);

                $logName = $model->getLogNameToUse($eventName);

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

    public function getLogNameToUse(string $eventName = ''): string
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

        $events = [
            'created',
            'updated',
            'deleted',
        ];

        if(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(__CLASS__))) {
            $events[] = 'restored';
        }

        return collect($events);
    }

    public function attributesToBeIgnored(): array
    {
        if (! isset(static::$ignoreChangedAttributes)) {
            return [];
        }

        return static::$ignoreChangedAttributes;
    }
}
