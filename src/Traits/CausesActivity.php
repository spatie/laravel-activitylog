<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\ActivitylogConfig;
use Spatie\Activitylog\Models\Activity;

trait CausesActivity
{
    /** @return MorphMany<Activity, $this> */
    public function activitiesAsCauser(): MorphMany
    {
        return $this->morphMany(
            ActivitylogConfig::activityModel(),
            'causer'
        );
    }
}
