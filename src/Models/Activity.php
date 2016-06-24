<?php

namespace Spatie\Activitylog\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Eloquent
{
    protected $table = 'activity_log';

    protected $casts = [
        'extra_properties' => 'collection',
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
        return array_get($this->extra_properties, $propertyName);
    }
}
