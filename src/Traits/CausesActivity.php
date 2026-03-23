<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Support\Config;

trait CausesActivity
{
    /** @return MorphMany<Activity, $this> */
    public function activitiesAsCauser(): MorphMany
    {
        return $this->morphMany(
            Config::activityModel(),
            'causer'
        );
    }
}
