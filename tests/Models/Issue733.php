<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\ActivitylogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Issue733 extends Article
{
    use LogsActivity;

    protected static $recordEvents = [
        'retrieved',
    ];

    public function getActivitylogOptions() : ActivitylogOptions
    {
        return ActivitylogOptions::create()
        ->dontSubmitEmptyLogs()
        ->logOnly(['name']);
    }
}
