<?php

namespace Spatie\Activitylog;

use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin \Spatie\Activitylog\ActivityLogger
 */
class PendingActivityLog
{
    use ForwardsCalls;

    protected ActivityLogger $logger;

    public function __construct(ActivityLogger $logger, ActivityLogStatus $status)
    {
        $this->logger = $logger
            ->setLogStatus($status)
            ->useLog(config('activitylog.default_log_name'));
    }

    public function logger(): ActivityLogger
    {
        return $this->logger;
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->logger, $method, $parameters);
    }
}
