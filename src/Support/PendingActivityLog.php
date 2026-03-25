<?php

namespace Spatie\Activitylog\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Contracts\Activity;

/**
 * @mixin ActivityLogger
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

    /** @param Closure(Activity): void $callback */
    public static function beforeLogging(Closure $callback): void
    {
        LogActivityAction::beforeLogging($callback);
    }

    public function defaultCauser(?Model $causer, ?Closure $callback = null): mixed
    {
        $resolver = app(CauserResolver::class);

        if ($callback) {
            return $resolver->withCauser($causer, $callback);
        }

        $resolver->setCauser($causer);

        return $this;
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->logger, $method, $parameters);
    }
}
