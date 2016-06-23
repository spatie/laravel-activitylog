<?php

use Spatie\Activitylog\ActivityLogger;

if (! function_exists('activity')) {

    function activity(): ActivityLogger {
        return app(ActivityLogger::class);
    }
    
}