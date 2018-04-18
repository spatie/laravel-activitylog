<?php

namespace Spatie\Activitylog\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;

class InvalidConfiguration extends Exception
{
    public static function modelIsNotValid(string $className)
    {
        return new static("The given model class `$className` does not implements `".Activity::class.'` and extend `'.Model::class.'`');
    }
}
