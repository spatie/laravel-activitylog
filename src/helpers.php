<?php

use Spatie\Activitylog\ActivityLogger;

if (!function_exists('activity')) {
    /**
     * @param string|null $logName
     *
     * @return \Spatie\Activitylog\ActivityLogger
     */
    function activity($logName = null)
    {
        $defaultLogName = config('laravel-activitylog.default_log_name');

        return app(ActivityLogger::class)->useLog(isset($logName) ? $logName : $defaultLogName);
    }
}
