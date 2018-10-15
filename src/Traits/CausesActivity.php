<?php

namespace Spatie\Activitylog\Traits;

use Spatie\Activitylog\ActivitylogServiceProvider;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CausesActivity
{
    public function activity(): MorphMany
    {
        return $this->morphMany(ActivitylogServiceProvider::determineActivityModel(), 'causer');
    }
}
