<?php

namespace Spatie\Activitylog\Traits;

use Carbon\CarbonInterval;
use DateInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Support\ActivityLogger;
use Spatie\Activitylog\Support\ActivityLogStatus;
use Spatie\Activitylog\Support\Config;
use Spatie\Activitylog\Support\EventLogBag;
use Spatie\Activitylog\Support\LogOptions;

trait LogsActivity
{
    public static array $changesPipes = [];

    protected array $oldAttributes = [];

    protected ?LogOptions $activitylogOptions;

    public bool $enableLoggingModelsEvents = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    protected static function bootLogsActivity(): void
    {
        // Hook into eloquent events that only specified in $eventToBeRecorded array,
        // checking for "updated" event hook explicitly to temporary hold original
        // attributes on the model as we'll need them later to compare against.

        static::eventsToBeRecorded()->each(function (string $eventName) {
            if ($eventName === ActivityEvent::Updated->value) {
                static::updating(function (Model $model) {
                    $oldValues = (new static())->setRawAttributes($model->getRawOriginal());
                    $model->oldAttributes = static::logChanges($oldValues);
                });
            }

            static::$eventName(function (Model $model) use ($eventName) {
                $model->activitylogOptions = $model->getActivitylogOptions();

                if (! $model->shouldLogEvent($eventName)) {
                    return;
                }

                $changes = $model->attributeValuesToBeLogged($eventName);

                $description = $model->getDescriptionForEvent($eventName);

                $logName = $model->getLogNameToUse();

                // Submitting empty description will cause place holder replacer to fail.
                if ($description === '') {
                    return;
                }

                // User can define a custom pipelines to mutate, add or remove from changes
                // each pipe receives the event carrier bag with changes and the model in
                // question every pipe should manipulate new and old attributes.
                $event = app(Pipeline::class)
                    ->send(new EventLogBag($eventName, $model, $changes, $model->activitylogOptions))
                    ->through(static::$changesPipes)
                    ->thenReturn();

                // Check for empty logs after pipeline has run
                if ($model->isLogEmpty($event->changes)) {
                    if (! $model->activitylogOptions->logEmptyChanges) {
                        return;
                    }
                }

                // Actual logging
                app(ActivityLogger::class)
                    ->useLog($logName)
                    ->event($eventName)
                    ->performedOn($model)
                    ->withChanges($event->changes)
                    ->log($description);

                // Reset log options so the model can be serialized.
                $model->activitylogOptions = null;
            });
        });
    }

    public static function addLogChange(object $pipe): void
    {
        static::$changesPipes[] = $pipe;
    }

    public function isLogEmpty(array $changes): bool
    {
        if (! empty($changes['attributes'] ?? [])) {
            return false;
        }

        return empty($changes['old'] ?? []);
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

    public function activitiesAsSubject(): MorphMany
    {
        return $this->morphMany(Config::activityModel(), 'subject');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        if (! empty($this->activitylogOptions->descriptionForEvent)) {
            return ($this->activitylogOptions->descriptionForEvent)($eventName);
        }

        return $eventName;
    }

    public function getLogNameToUse(): ?string
    {
        if (! empty($this->activitylogOptions->logName)) {
            return $this->activitylogOptions->logName;
        }

        return config('activitylog.default_log_name');
    }

    /**
     * Get the event names that should be recorded.
     **/
    protected static function eventsToBeRecorded(): Collection
    {
        $reject = collect(static::$doNotRecordEvents ?? []);

        if (isset(static::$recordEvents)) {
            return collect(static::$recordEvents)->reject(fn (string $eventName) => $reject->contains($eventName));
        }

        $events = collect([
            ActivityEvent::Created->value,
            ActivityEvent::Updated->value,
            ActivityEvent::Deleted->value,
        ]);

        if (collect(class_uses_recursive(static::class))->contains(SoftDeletes::class)) {
            $events->push(ActivityEvent::Restored->value);
        }

        return $events->reject(fn (string $eventName) => $reject->contains($eventName));
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        $logStatus = app(ActivityLogStatus::class);

        if (! $this->enableLoggingModelsEvents) {
            return false;
        }

        if ($logStatus->disabled()) {
            return false;
        }

        if (! in_array($eventName, [ActivityEvent::Created->value, ActivityEvent::Updated->value])) {
            return true;
        }

        // Do not log update event if the model is restoring
        if ($this->isRestoring()) {
            return false;
        }

        // Do not log update event if only ignored attributes are changed.
        return (bool) count(array_diff_key($this->getDirty(), array_flip($this->activitylogOptions->dontLogIfAttributesChangedOnly)));
    }

    /**
     * Determines if the model is restoring.
     **/
    protected function isRestoring(): bool
    {
        $deletedAtColumn = method_exists($this, 'getDeletedAtColumn')
            ? $this->getDeletedAtColumn()
            : 'deleted_at';

        if (! $this->isDirty($deletedAtColumn)) {
            return false;
        }

        return count($this->getDirty()) === 1;
    }

    /**
     * Determines what attributes needs to be logged based on the configuration.
     **/
    public function attributesToBeLogged(): array
    {
        $this->activitylogOptions = $this->getActivitylogOptions();

        $attributes = [];

        // Check if fillable attributes will be logged then merge it to the local attributes array.
        if ($this->activitylogOptions->logFillable) {
            $attributes = array_merge($attributes, $this->getFillable());
        }

        // Determine if unguarded attributes will be logged.
        if ($this->shouldLogUnguarded()) {
            // If globally unguarded, log all attributes
            if (static::isUnguarded()) {
                $attributes = array_merge($attributes, array_keys($this->getAttributes()));
            } else {
                // Get only attribute names, not interested in the values here then guarded
                // attributes. get only keys than not present in guarded array, because
                // we are logging the unguarded attributes and we can't have both!
                $attributes = array_merge($attributes, array_diff(array_keys($this->getAttributes()), $this->getGuarded()));
            }
        }

        if (! empty($this->activitylogOptions->logAttributes)) {

            // Filter * from the logAttributes because will deal with it separately
            $attributes = array_merge($attributes, array_diff($this->activitylogOptions->logAttributes, ['*']));

            // If there's * get all attributes then merge it, dont respect $guarded or $fillable.
            if (in_array('*', $this->activitylogOptions->logAttributes)) {
                $attributes = array_merge($attributes, array_keys($this->getAttributes()));
            }
        }

        if ($this->activitylogOptions->logExceptAttributes) {

            // Filter out the attributes defined in ignoredAttributes out of the local array
            $attributes = array_diff($attributes, $this->activitylogOptions->logExceptAttributes);
        }

        // Merge global exclusions from config
        $globalExcept = config('activitylog.default_except_attributes', []);
        if (! empty($globalExcept)) {
            $attributes = array_diff($attributes, $globalExcept);
        }

        return $attributes;
    }

    public function shouldLogUnguarded(): bool
    {
        if (! $this->activitylogOptions->logUnguarded) {
            return false;
        }

        // If the model is globally unguarded via Model::unguard(),
        // all attributes should be considered unguarded.
        if (static::isUnguarded()) {
            return true;
        }

        // This case means all of the attributes are guarded
        // so we'll not have any unguarded anyway.
        if (in_array('*', $this->getGuarded())) {
            return false;
        }

        return true;
    }

    /**
     * Determines values that will be logged based on the difference.
     **/
    public function attributeValuesToBeLogged(string $processingEvent): array
    {
        if (! count($this->attributesToBeLogged())) {
            return [];
        }

        $properties['attributes'] = static::logChanges(
            $this->resolveModelForLogging($processingEvent)
        );

        if ($this->isUpdatedEvent($processingEvent)) {
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);

            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);

            $this->oldAttributes = [];
        }

        if ($this->shouldLogOnlyDirtyAttributes($properties)) {
            $properties = $this->filterDirtyAttributes($properties);
        }

        if ($this->isDeletedEvent($processingEvent)) {
            $properties['old'] = $properties['attributes'];
            unset($properties['attributes']);
        }

        return $properties;
    }

    protected function resolveModelForLogging(string $processingEvent): static
    {
        if ($processingEvent === 'retrieved') {
            return $this;
        }

        if (! $this->exists) {
            return $this;
        }

        return $this->fresh() ?? $this;
    }

    protected function shouldLogOnlyDirtyAttributes(array $properties): bool
    {
        if (! $this->activitylogOptions->logOnlyDirty) {
            return false;
        }

        return isset($properties['old']);
    }

    protected function filterDirtyAttributes(array $properties): array
    {
        $properties['attributes'] = array_udiff_assoc(
            $properties['attributes'],
            $properties['old'],
            function ($new, $old) {
                if ($old === null) {
                    return $new === null ? 0 : 1;
                }

                if ($new === null) {
                    return 1;
                }

                if ($old instanceof DateInterval) {
                    return CarbonInterval::make($old)->equalTo($new) ? 0 : 1;
                }

                if ($new instanceof DateInterval) {
                    return CarbonInterval::make($new)->equalTo($old) ? 0 : 1;
                }

                return $new <=> $old;
            }
        );

        $properties['old'] = collect($properties['old'])
            ->only(array_keys($properties['attributes']))
            ->all();

        return $properties;
    }

    protected function isUpdatedEvent(string $processingEvent): bool
    {
        if (! static::eventsToBeRecorded()->contains(ActivityEvent::Updated->value)) {
            return false;
        }

        return $processingEvent === ActivityEvent::Updated->value;
    }

    protected function isDeletedEvent(string $processingEvent): bool
    {
        if (! static::eventsToBeRecorded()->contains(ActivityEvent::Deleted->value)) {
            return false;
        }

        return $processingEvent === ActivityEvent::Deleted->value;
    }

    public static function logChanges(Model $model): array
    {
        $changes = [];
        $attributes = $model->attributesToBeLogged();

        foreach ($attributes as $attribute) {
            if (Str::contains($attribute, '.')) {
                $changes += self::getRelatedModelAttributeValue($model, $attribute);

                continue;
            }

            if (Str::contains($attribute, '->')) {
                data_set(
                    $changes,
                    str_replace('->', '.', $attribute),
                    static::getModelAttributeJsonValue($model, $attribute)
                );

                continue;
            }

            $changes[$attribute] = in_array($attribute, $model->activitylogOptions->attributeRawValues)
                ? $model->getAttributeFromArray($attribute)
                : $model->getAttribute($attribute);

            if (is_null($changes[$attribute])) {
                continue;
            }

            if ($model->isDateAttribute($attribute)) {
                $changes[$attribute] = $model->serializeDate(
                    $model->asDateTime($changes[$attribute])
                );
            }

            if ($model->hasCast($attribute)) {
                $cast = $model->getCasts()[$attribute];

                if ($model->isEnumCastable($attribute)) {
                    $changes[$attribute] = $model->getStorableEnumValue($cast, $changes[$attribute]);
                }

                if ($model->isCustomDateTimeCast($cast)) {
                    $changes[$attribute] = $model->asDateTime($changes[$attribute])->format(explode(':', $cast, 2)[1]);
                }

                if ($model->isImmutableCustomDateTimeCast($cast)) {
                    $changes[$attribute] = $model->asDateTime($changes[$attribute])->format(explode(':', $cast, 2)[1]);
                }
            }
        }

        return $changes;
    }

    protected static function getRelatedModelAttributeValue(Model $model, string $attribute): array
    {
        $relatedModelNames = explode('.', $attribute);
        $relatedAttribute = array_pop($relatedModelNames);

        $attributeName = [];
        $relatedModel = $model;

        do {
            $attributeName[] = $relatedModelName = static::getRelatedModelRelationName($relatedModel, array_shift($relatedModelNames));

            $relatedModel = $relatedModel->$relatedModelName ?? $relatedModel->$relatedModelName();
        } while (! empty($relatedModelNames));

        $attributeName[] = $relatedAttribute;

        return [implode('.', $attributeName) => $relatedModel->$relatedAttribute ?? null];
    }

    protected static function getRelatedModelRelationName(Model $model, string $relation): string
    {
        $candidates = [$relation, Str::snake($relation), Str::camel($relation)];

        return array_find(
            $candidates,
            fn (string $method): bool => method_exists($model, $method)
        ) ?? $relation;
    }

    protected static function getModelAttributeJsonValue(Model $model, string $attribute): mixed
    {
        $path = explode('->', $attribute);
        $modelAttribute = array_shift($path);
        $modelAttribute = collect($model->getAttribute($modelAttribute));

        return data_get($modelAttribute, implode('.', $path));
    }
}
