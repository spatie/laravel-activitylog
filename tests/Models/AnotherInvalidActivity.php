<?php

namespace Spatie\Activitylog\Test\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class AnotherInvalidActivity implements ActivityContract
{
    protected $table;

    public $guarded = [];

    protected $casts = [
        'properties' => 'collection',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = config('activitylog.table_name');
    }

    public function subject(): MorphTo
    {
        if (config('activitylog.subject_returns_soft_deleted_models')) {
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
