<?php

namespace Spatie\Activitylog\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;

class InvalidConfiguration extends Exception
{
    public static function modelIsNotValid(string $className): self
    {
        return new self("The given model class `{$className}` does not implement `".Activity::class.'` or it does not extend `'.Model::class.'`');
    }

    public static function actionIsNotValid(string $className, string $expectedBaseClass): self
    {
        return new self("The given action class `{$className}` does not extend `{$expectedBaseClass}`.");
    }
}
