<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Traits\LogsActivity;

class Issue733 extends Article
{
    use LogsActivity;

    protected static $recordEvents = [
        'retrieved',
    ];

    protected static $submitEmptyLogs = false;
    protected static $logAttributes = ['name'];
    public static $logOnlyDirty = false;
}
