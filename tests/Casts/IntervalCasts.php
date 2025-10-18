<?php

namespace Spatie\Activitylog\Test\Casts;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class IntervalCasts implements CastsAttributes
{
    public function get($model, string $key, mixed $value, array $attributes): ?string
    {
        if (empty($value)) {
            return null;
        }

        return (string) CarbonInterval::create($value);
    }

    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = is_string($value) ? CarbonInterval::create($value) : $value;

        return CarbonInterval::getDateIntervalSpec($value);
    }
}
