<?php

namespace Spatie\Activitylog\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class Activity extends Eloquent
{
    protected $table = 'activity_log';

    protected $casts = [
        'properties' => 'collection',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the extra properties with the given name.
     *
     * @param $propertyName
     *
     * @return mixed
     */
    public function getExtraProperty(string $propertyName)
    {
        return array_get($this->properties, $propertyName);
    }

    public function getChangesAttribute(): Collection
    {
        return collect($this->properties->pluck(['attributes', 'old']));
    }
}
