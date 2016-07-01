<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Activity;

trait SubjectsActivity
{
    public function loggedActivity(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}
