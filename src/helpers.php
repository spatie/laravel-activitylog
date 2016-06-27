<?php

use Spatie\Activitylog\ActivityLogger;

if (!function_exists('activity')) {
    
    function activity(string $logName = ''): ActivityLogger
    {
        return app(ActivityLogger::class)->useLog($logName);
    }
}
