<?php

namespace Spatie\Activitylog\Exceptions;

use Exception;

class CouldNotLogActivity extends Exception
{
    public static function couldNotDetermineUser($id): self
    {
        return new static("Could not determine a user with identifier `{$id}`.");
    }
}
