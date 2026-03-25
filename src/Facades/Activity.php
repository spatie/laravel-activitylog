<?php

namespace Spatie\Activitylog\Facades;

use Illuminate\Support\Facades\Facade;
use Spatie\Activitylog\Support\PendingActivityLog;

/**
 * @method static \Spatie\Activitylog\Support\ActivityLogger setLogStatus(\Spatie\Activitylog\Support\ActivityLogStatus $logStatus)
 * @method static \Spatie\Activitylog\Support\ActivityLogger performedOn(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Spatie\Activitylog\Support\ActivityLogger on(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Spatie\Activitylog\Support\ActivityLogger causedBy(\Illuminate\Database\Eloquent\Model|string|int|null $modelOrId)
 * @method static \Spatie\Activitylog\Support\ActivityLogger by(\Illuminate\Database\Eloquent\Model|string|int|null $modelOrId)
 * @method static \Spatie\Activitylog\Support\ActivityLogger causedByAnonymous()
 * @method static \Spatie\Activitylog\Support\ActivityLogger byAnonymous()
 * @method static \Spatie\Activitylog\Support\ActivityLogger event(string|\Spatie\Activitylog\Enums\ActivityEvent $event)
 * @method static \Spatie\Activitylog\Support\ActivityLogger setEvent(string|\Spatie\Activitylog\Enums\ActivityEvent $event)
 * @method static \Spatie\Activitylog\Support\ActivityLogger withProperties(array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $properties)
 * @method static \Spatie\Activitylog\Support\ActivityLogger withProperty(string $key, mixed $value)
 * @method static \Spatie\Activitylog\Support\ActivityLogger createdAt(\DateTimeInterface $dateTime)
 * @method static \Spatie\Activitylog\Support\ActivityLogger useLog(string|null $logName)
 * @method static \Spatie\Activitylog\Support\ActivityLogger inLog(string|null $logName)
 * @method static \Spatie\Activitylog\Support\ActivityLogger tap(callable $callback, string|null $eventName = null)
 * @method static \Spatie\Activitylog\Support\ActivityLogger enableLogging()
 * @method static \Spatie\Activitylog\Support\ActivityLogger disableLogging()
 * @method static \Spatie\Activitylog\Contracts\Activity|null log(string $description)
 * @method static \Spatie\Activitylog\Support\ActivityLogger withChanges(array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $changes)
 * @method static mixed withoutLogging(\Closure $callback)
 * @method static void beforeLogging(\Closure $callback)
 * @method static mixed defaultCauser(\Illuminate\Database\Eloquent\Model|null $causer, \Closure|null $callback = null)
 * @method static \Spatie\Activitylog\Support\ActivityLogger|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Spatie\Activitylog\Support\ActivityLogger|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see PendingActivityLog
 */
class Activity extends Facade
{
    protected static $cached = false;

    protected static function getFacadeAccessor(): string
    {
        return PendingActivityLog::class;
    }
}
