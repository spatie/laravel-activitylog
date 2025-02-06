<?php

use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\PendingActivityLog;

if (! function_exists('activity')) {
    function activity(?string $logName = null): ActivityLogger
    {
        /** @var PendingActivityLog $log */
        $log = app(PendingActivityLog::class);

        if ($logName) {
            $log->useLog($logName);
        }

        return $log->logger();
    }
}
