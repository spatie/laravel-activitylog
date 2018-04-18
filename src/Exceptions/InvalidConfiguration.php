<?php

namespace Spatie\Activitylog\Exceptions;

use Exception;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Model;

class InvalidConfiguration extends Exception
{
    public static function modelIsNotValid(string $className)
    {
        return new static("The given model class `$className` does not implements `".Activity::class.'` and extend `'.Model::class.'`');
    }
}
