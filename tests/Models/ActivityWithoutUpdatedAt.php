<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Models\Activity;

class ActivityWithoutUpdatedAt extends Activity
{
    public const UPDATED_AT = null;
}
