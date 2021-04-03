<?php
namespace Spatie\Activitylog\Facades;

use Illuminate\Support\Facades\Facade;
use Spatie\Activitylog\ActivityLoggerBatch;

/**
 * @method static string getUuid()
 * @method static mixed useBatch(\Closure $callback)
 * @method static void startBatch()
 * @method static bool isOpen()
 * @method static void endBatch()
 *
 * @see \Spatie\Activitylog\ActivityLoggerBatch
 */
class LogBatch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivityLoggerBatch::class;
    }
}
