<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Issue733 extends Article
{
    use LogsActivity;

    protected static $recordEvents = [
        'retrieved',
    ];

    public function getActivitylogOptions() : LogOptions
    {
        return LogOptions::defaults()
        ->dontSubmitEmptyLogs()
        ->logOnly(['name']);
    }
}
