<?php

namespace Spatie\Activitylog\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Spatie\Activitylog\CauserResolver as ActivitylogCauserResolver;

/**
 * @method static null|Model resolve(Model|int|string|null $subject = null)
 * @method static ActivitylogCauserResolver resolveUsing(Closure $callback)
 * @method static ActivitylogCauserResolver setCauser(?Model $causer)
 *
 * @see \Spatie\Activitylog\CauserResolver
 */
class CauserResolver extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivitylogCauserResolver::class;
    }
}
