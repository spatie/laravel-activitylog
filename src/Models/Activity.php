<?php

namespace Spatie\Activitylog\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class Activity extends Eloquent
{
    protected $table = 'activity_log';

    public $guarded = [];

    protected $casts = [
        'properties' => 'collection',
    ];

    public function subject(): MorphTo
    {
        if (config('laravel-activitylog.subject_returns_soft_deleted_models')) {
            return $this->morphTo()->withTrashed();
        }

        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the extra properties with the given name.
     *
     * @param string $propertyName
     *
     * @return mixed
     */
    public function getExtraProperty(string $propertyName)
    {
        return array_get($this->properties->toArray(), $propertyName);
    }

    public function getChangesAttribute(): Collection
    {
        return collect(array_filter($this->properties->toArray(), function ($key) {
            return in_array($key, ['attributes', 'old']);
        }, ARRAY_FILTER_USE_KEY));
    }

    public function scopeInLog(Builder $query, ...$logNames): Builder
    {
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('log_name', $logNames);
    }

    /**
     * Scope a query to only include activities by a give causer.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCauser(Builder $query, $causer): Builder
    {
        return $query->where('causer_id', $causer->getKey());
    }

    /**
     * Scope a query to only include activities for a give subject.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSubject(Builder $query, $subject): Builder
    {
        return $query->where('subject_id', $subject->getKey());
    }
}
