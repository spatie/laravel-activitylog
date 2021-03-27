<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\ActivityLogStatus;

trait LogsActivity
{
    use DetectsChanges;

    public bool $enableLoggingModelsEvents = true;

    protected static function bootLogsActivity(): void
    {
        static::eventsToBeRecorded()->each(function ($eventName) {
            return static::$eventName(function (Model $model) use ($eventName) {
                $model->activitylogOptions = $model->getActivitylogOptions();

                if (! $model->shouldLogEvent($eventName)) {
                    return;
                }

                $description = $model->getDescriptionForEvent($eventName);

                $logName = $model->getLogNameToUse();

                if ($description == '') {
                    return;
                }

                $attrs = $model->attributeValuesToBeLogged($eventName);

                if ($model->isLogEmpty($attrs) && ! $model->activitylogOptions->submitEmptyLogs) {
                    return;
                }

                $logger = app(ActivityLogger::class)
                    ->useLog($logName)
                    ->performedOn($model)
                    ->withProperties($attrs);

                if (method_exists($model, 'tapActivity')) {
                    $logger->tap([$model, 'tapActivity'], $eventName);
                }

                $logger->log($description);
            });
        });
    }


    public function isLogEmpty($attrs): bool
    {
        return empty($attrs['attributes'] ?? []) && empty($attrs['old'] ?? []);
    }

    public function disableLogging(): self
    {
        $this->enableLoggingModelsEvents = false;

        return $this;
    }

    public function enableLogging(): self
    {
        $this->enableLoggingModelsEvents = true;

        return $this;
    }


    public function activities(): MorphMany
    {
        return $this->morphMany(ActivitylogServiceProvider::determineActivityModel(), 'subject');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        if (! empty($this->activitylogOptions->descriptionForEvent)) {
            return ($this->activitylogOptions->descriptionForEvent)($eventName);
        }

        return $eventName;
    }

    public function getLogNameToUse(): string
    {
        if (! empty($this->activitylogOptions->logName)) {
            return $this->activitylogOptions->logName;
        }

        return config('activitylog.default_log_name');
    }

    /*
     ** Get the event names that should be recorded.
     */
    protected static function eventsToBeRecorded(): Collection
    {
        if (isset(static::$recordEvents)) {
            return collect(static::$recordEvents);
        }

        $events = collect([
            'created',
            'updated',
            'deleted',
        ]);

        if (collect(class_uses_recursive(static::class))->contains(SoftDeletes::class)) {
            $events->push('restored');
        }

        return $events;
    }


    protected function shouldLogEvent(string $eventName): bool
    {
        $logStatus = app(ActivityLogStatus::class);

        if (! $this->enableLoggingModelsEvents || $logStatus->disabled()) {
            return false;
        }

        if (! in_array($eventName, ['created', 'updated'])) {
            return true;
        }

        if (Arr::has($this->getDirty(), 'deleted_at')) {
            if ($this->getDirty()['deleted_at'] === null) {
                return false;
            }
        }

        //do not log update event if only ignored attributes are changed
        return (bool) count(Arr::except($this->getDirty(), $this->activitylogOptions->dontLogIfAttributesChangedBag));
    }
}
