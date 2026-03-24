<?php

namespace Spatie\Activitylog\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Support\ActivityLogger;
use Spatie\Activitylog\Support\ActivityLogStatus;
use Spatie\Activitylog\Support\ChangeDetector;
use Spatie\Activitylog\Support\Config;
use Spatie\Activitylog\Support\LogOptions;

trait LogsActivity
{
    protected array $oldAttributes = [];

    protected ?LogOptions $activitylogOptions;

    public bool $enableLoggingModelsEvents = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    protected static function bootLogsActivity(): void
    {
        static::eventsToBeRecorded()->each(function (string $eventName) {
            if ($eventName === ActivityEvent::Updated->value) {
                static::updating(function (Model $model) {
                    $oldValues = (new static)->setRawAttributes($model->getRawOriginal());
                    $model->oldAttributes = static::extractChanges($oldValues);
                });
            }

            static::$eventName(function (Model $model) use ($eventName) {
                $model->activitylogOptions = $model->getActivitylogOptions();

                if (! $model->shouldLogEvent($eventName)) {
                    return;
                }

                $description = $model->getDescriptionForEvent($eventName);

                if ($description === '') {
                    return;
                }

                $changes = $model->buildChanges($eventName);

                if ($model->shouldSkipEmptyLog($changes)) {
                    return;
                }

                app(ActivityLogger::class)
                    ->useLog($model->getLogNameToUse())
                    ->event($eventName)
                    ->performedOn($model)
                    ->withChanges($changes)
                    ->log($description);

                $model->activitylogOptions = null;
            });
        });
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

    /** @return MorphMany<Activity, $this> */
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
        return $this->activitylogOptions->logName
            ?? config('activitylog.default_log_name');
    }

    protected static function eventsToBeRecorded(): Collection
    {
        $reject = collect(static::$doNotRecordEvents ?? []);

        if (isset(static::$recordEvents)) {
            return collect(static::$recordEvents)
                ->reject(fn (string $eventName) => $reject->contains($eventName));
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
        if (! $this->enableLoggingModelsEvents) {
            return false;
        }

        if (app(ActivityLogStatus::class)->disabled()) {
            return false;
        }

        if (! in_array($eventName, [ActivityEvent::Created->value, ActivityEvent::Updated->value])) {
            return true;
        }

        if ($this->isRestoring()) {
            return false;
        }

        return $this->hasChangedAttributesBeyondIgnored();
    }

    protected function isRestoring(): bool
    {
        $deletedAtColumn = method_exists($this, 'getDeletedAtColumn')
            ? $this->getDeletedAtColumn()
            : 'deleted_at';

        if (! $this->isDirty($deletedAtColumn)) {
            return false;
        }

        return ! is_null($this->getOriginal($deletedAtColumn))
            && is_null($this->getAttribute($deletedAtColumn));
    }

    protected function hasChangedAttributesBeyondIgnored(): bool
    {
        $dirty = array_diff_key(
            $this->getDirty(),
            array_flip($this->activitylogOptions->dontLogIfAttributesChangedOnly)
        );

        return count($dirty) > 0;
    }

    public function attributesToBeLogged(): array
    {
        $this->activitylogOptions = $this->getActivitylogOptions();

        return collect()
            ->merge($this->fillableAttributes())
            ->merge($this->unguardedAttributes())
            ->merge($this->explicitAttributes())
            ->diff($this->excludedAttributes())
            ->unique()
            ->values()
            ->all();
    }

    protected function fillableAttributes(): array
    {
        if (! $this->activitylogOptions->logFillable) {
            return [];
        }

        return $this->getFillable();
    }

    protected function unguardedAttributes(): array
    {
        if (! $this->activitylogOptions->logUnguarded) {
            return [];
        }

        if (static::isUnguarded()) {
            return array_keys($this->getAttributes());
        }

        if (in_array('*', $this->getGuarded())) {
            return [];
        }

        return array_diff(array_keys($this->getAttributes()), $this->getGuarded());
    }

    protected function explicitAttributes(): array
    {
        if (empty($this->activitylogOptions->logAttributes)) {
            return [];
        }

        $explicit = array_diff($this->activitylogOptions->logAttributes, ['*']);

        if (in_array('*', $this->activitylogOptions->logAttributes)) {
            $explicit = array_merge($explicit, array_keys($this->getAttributes()));
        }

        return $explicit;
    }

    protected function excludedAttributes(): array
    {
        return array_merge(
            $this->activitylogOptions->logExceptAttributes,
            config('activitylog.default_except_attributes', [])
        );
    }

    protected function buildChanges(string $processingEvent): array
    {
        if (! count($this->attributesToBeLogged())) {
            return [];
        }

        $model = $this->resolveModelForLogging($processingEvent);
        $properties['attributes'] = static::extractChanges($model);

        if ($this->isUpdatedEvent($processingEvent)) {
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);
            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);
            $this->oldAttributes = [];
        }

        if ($this->activitylogOptions->logOnlyDirty) {
            if (isset($properties['old'])) {
                $filtered = ChangeDetector::filterDirty($properties['attributes'], $properties['old']);
                $properties['attributes'] = $filtered['attributes'];
                $properties['old'] = $filtered['old'];
            }
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

    protected function shouldSkipEmptyLog(array $changes): bool
    {
        if ($this->activitylogOptions->logEmptyChanges) {
            return false;
        }

        if (! empty($changes['attributes'] ?? [])) {
            return false;
        }

        return empty($changes['old'] ?? []);
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

    public static function extractChanges(Model $model): array
    {
        $options = $model->activitylogOptions ?? $model->getActivitylogOptions();
        $changes = [];

        foreach ($model->attributesToBeLogged() as $attribute) {
            if (Str::contains($attribute, '.')) {
                $changes += ChangeDetector::resolveRelatedAttribute($model, $attribute);

                continue;
            }

            if (Str::contains($attribute, '->')) {
                data_set(
                    $changes,
                    str_replace('->', '.', $attribute),
                    ChangeDetector::resolveJsonAttribute($model, $attribute)
                );

                continue;
            }

            $changes += static::resolveAttributeValue($model, $attribute, $options);
        }

        return $changes;
    }

    protected static function resolveAttributeValue(Model $model, string $attribute, LogOptions $options): array
    {

        $value = in_array($attribute, $options->attributeRawValues)
            ? $model->getAttributeFromArray($attribute)
            : $model->getAttribute($attribute);

        if (is_null($value)) {
            return [$attribute => null];
        }

        return [$attribute => static::formatAttributeValue($model, $attribute, $value)];
    }

    protected static function formatAttributeValue(Model $model, string $attribute, mixed $value): mixed
    {
        if ($model->hasCast($attribute)) {
            $cast = $model->getCasts()[$attribute];

            if ($model->isEnumCastable($attribute)) {
                return $model->getStorableEnumValue($cast, $value);
            }

            if ($model->isCustomDateTimeCast($cast)) {
                return $model->asDateTime($value)->format(explode(':', $cast, 2)[1]);
            }

            if ($model->isImmutableCustomDateTimeCast($cast)) {
                return $model->asDateTime($value)->format(explode(':', $cast, 2)[1]);
            }
        }

        if ($model->isDateAttribute($attribute)) {
            return $model->serializeDate($model->asDateTime($value));
        }

        return $value;
    }
}
