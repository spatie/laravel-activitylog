<?php

namespace Spatie\Activitylog;

use Illuminate\Support\Facades\Facade;

class ActivitylogFacade extends Facade
{
    /**
     * @see \Spatie\Activitylog\ActivityLogger
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-activitylog';
    }
}
