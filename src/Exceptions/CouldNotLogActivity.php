<?php

namespace Spatie\Activitylog\Exceptions;

use Exception;

class CouldNotLogActivity extends Exception
{
    public static function couldNotDetermineUser($id)
    {
    	// [new self] refers to the same class in which the new keyword is actually written.

		// [new static], in PHP 5.3's late static bindings, refers to whatever class in the hierarchy you called the method on.
        return new static("Could not determine a user with identifier `{$id}`.");
    }
}
