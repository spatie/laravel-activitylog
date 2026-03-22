<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
trait HasActivity
{
    use LogsActivity;
    use CausesActivity;

    public function activities(): MorphMany
    {
        return $this->activitiesAsSubject();
    }
}
