<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Activity;

trait CausesActivity
{
    public function activity(): MorphMany
    {
        return $this->morphMany(Activity::class, 'causer');
    }

    /** @deprecated Use activity() instead */
    public function loggedActivity(): MorphMany
    {
        return $this->activity();
    }
}
