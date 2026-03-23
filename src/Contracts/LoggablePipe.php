<?php

namespace Spatie\Activitylog\Contracts;

use Closure;
use Spatie\Activitylog\Support\EventLogBag;

interface LoggablePipe
{
    public function handle(EventLogBag $event, Closure $next): EventLogBag;
}
