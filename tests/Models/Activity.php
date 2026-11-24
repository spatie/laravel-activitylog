<?php

namespace Spatie\Activitylog\Test\Models;

use BackedEnum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Arr;
use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\MorphTo;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Enums\ActivityEvent;

class Activity extends Model implements ActivityContract
{
    protected $table = 'activity_log';

    public $guarded = [];

    protected $casts = [
        'attribute_changes' => 'collection',
        'properties' => 'collection',
    ];

    public function subject(): MorphTo
    {
        if (config('activitylog.include_soft_deleted_subjects')) {
            return $this->morphTo()->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function getProperty(string $propertyName, mixed $defaultValue = null): mixed
    {
        return Arr::get($this->properties?->toArray() ?? [], $propertyName, $defaultValue);
    }

    /** @param  BackedEnum|string|array<BackedEnum|string>  ...$logNames */
    public function scopeInLog(Builder $query, BackedEnum|string|array ...$logNames): Builder
    {
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('log_name', array_map(
            fn (BackedEnum|string $logName) => $logName instanceof BackedEnum ? $logName->value : $logName,
            $logNames,
        ));
    }

    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    public function scopeForEvent(Builder $query, string|ActivityEvent $event): Builder
    {
        return $query->where('event', $event instanceof ActivityEvent ? $event->value : $event);
    }

    public function getCustomPropertyAttribute()
    {
        return $this->attribute_changes;
    }
}
