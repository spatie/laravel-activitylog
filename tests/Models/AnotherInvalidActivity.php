<?php

namespace Spatie\Activitylog\Test\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class AnotherInvalidActivity implements ActivityContract
{
    protected $table = 'activity_log';

    public $guarded = [];

    protected $casts = [
        'properties' => 'collection',
    ];

    public function subject(): MorphTo
    {
        if (config('activitylog.include_soft_deleted_subjects')) {
            return $this->morphTo()->withTrashed();
        }

        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function getProperty(string $propertyName, mixed $defaultValue = null): mixed
    {
        return Arr::get($this->properties->toArray(), $propertyName, $defaultValue);
    }
}
