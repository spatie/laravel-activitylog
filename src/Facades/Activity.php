<?php

namespace Spatie\Activitylog\Facades;

use Illuminate\Support\Facades\Facade;
use Spatie\Activitylog\PendingActivityLog;

/**
 * @method static \Spatie\Activitylog\ActivityLogger setLogStatus(\Spatie\Activitylog\ActivityLogStatus $logStatus)
 * @method static \Spatie\Activitylog\ActivityLogger performedOn(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Spatie\Activitylog\ActivityLogger on(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Spatie\Activitylog\ActivityLogger causedBy(\Illuminate\Database\Eloquent\Model|string|int|null $modelOrId)
 * @method static \Spatie\Activitylog\ActivityLogger by(\Illuminate\Database\Eloquent\Model|string|int|null $modelOrId)
 * @method static \Spatie\Activitylog\ActivityLogger causedByAnonymous()
 * @method static \Spatie\Activitylog\ActivityLogger byAnonymous()
 * @method static \Spatie\Activitylog\ActivityLogger event(string $event)
 * @method static \Spatie\Activitylog\ActivityLogger setEvent(string $event)
 * @method static \Spatie\Activitylog\ActivityLogger withProperties(mixed $properties)
 * @method static \Spatie\Activitylog\ActivityLogger withProperty(string $key, mixed $value)
 * @method static \Spatie\Activitylog\ActivityLogger createdAt(\DateTimeInterface $dateTime)
 * @method static \Spatie\Activitylog\ActivityLogger useLog(string|null $logName)
 * @method static \Spatie\Activitylog\ActivityLogger inLog(string|null $logName)
 * @method static \Spatie\Activitylog\ActivityLogger tap(callable $callback, string|null $eventName = null)
 * @method static \Spatie\Activitylog\ActivityLogger enableLogging()
 * @method static \Spatie\Activitylog\ActivityLogger disableLogging()
 * @method static \Spatie\Activitylog\Contracts\Activity|null log(string $description)
 * @method static mixed withoutLogs(\Closure $callback)
 * @method static \Spatie\Activitylog\ActivityLogger|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Spatie\Activitylog\ActivityLogger|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Spatie\Activitylog\PendingActivityLog
 */
class Activity extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PendingActivityLog::class;
    }
}
