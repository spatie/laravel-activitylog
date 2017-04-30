<?php

namespace Spatie\Activitylog\Traits;

use Spatie\Activitylog\ActivitylogServiceProvider;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CausesActivity
{
    public function caused(): MorphMany
    {
        return $this->morphMany(ActivitylogServiceProvider::determineActivityModel(), 'causer');
    }

    /** @deprecated Use activity() instead */
    public function loggedActivity(): MorphMany
    {
        return $this->caused();
    }
}
