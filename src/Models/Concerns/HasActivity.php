<?php

namespace Spatie\Activitylog\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Activity;

trait HasActivity
{
    use CausesActivity;
    use LogsActivity;

    /** @return MorphMany<Activity, $this> */
    public function activities(): MorphMany
    {
        return $this->activitiesAsSubject();
    }
}
