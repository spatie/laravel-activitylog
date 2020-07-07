<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Traits\LogsActivity;

class Issue745 extends Article
{
    use LogsActivity;

    protected static $logAttributes = ['*'];
    protected static $ignoreChangedAttributes = ['created_at', 'updated_at', 'deleted_at'];
    protected static $logAttributesToIgnore = ['created_at', 'updated_at', 'deleted_at'];
    protected static $submitEmptyLogs = false;
}
