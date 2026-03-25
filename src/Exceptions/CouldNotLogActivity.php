<?php

namespace Spatie\Activitylog\Exceptions;

use Exception;

class CouldNotLogActivity extends Exception
{
    public static function couldNotDetermineUser(mixed $id): self
    {
        return new self("Could not determine a user with identifier `{$id}`.");
    }
}
