<?php

namespace Spatie\Activitylog;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Spatie\Activitylog\ActivityLogger
 */
class ActivitylogFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-activitylog';
    }
}
