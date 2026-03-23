<?php

namespace Spatie\Activitylog\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasActivity
{
    use LogsActivity;
    use CausesActivity;

    /** @return MorphMany<\Spatie\Activitylog\Models\Activity, $this> */
    public function activities(): MorphMany
    {
        return $this->activitiesAsSubject();
    }
}
