<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Traits\LogsActivity;

class Issue719Sub extends Article
{
    use LogsActivity;

    protected static $submitEmptyLogs = false;
    protected static $logAttributes = ['name'];
    public static $logOnlyDirty = false;
}
