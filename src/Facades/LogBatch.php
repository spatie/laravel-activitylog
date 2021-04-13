<?php
namespace Spatie\Activitylog\Facades;

use Illuminate\Support\Facades\Facade;
use Spatie\Activitylog\LoggerBatch;

/**
 * @method static string getUuid()
 * @method static mixed useBatch(\Closure $callback)
 * @method static void startBatch()
 * @method static bool isOpen()
 * @method static void endBatch()
 *
 * @see \Spatie\Activitylog\LoggerBatch
 */
class LogBatch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LoggerBatch::class;
    }
}
