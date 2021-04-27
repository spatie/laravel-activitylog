<?php

namespace Spatie\Activitylog\Test\Casts;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class IntervalCasts implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (empty($value)) {
            return null;
        }

        return (string) CarbonInterval::create($value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (empty($value)) {
            return null;
        }

        $value = is_string($value) ? CarbonInterval::create($value) : $value;

        return CarbonInterval::getDateIntervalSpec($value);
    }
}
