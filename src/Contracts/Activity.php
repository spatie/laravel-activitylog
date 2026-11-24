<?php

namespace Spatie\Activitylog\Contracts;

use MongoDB\Laravel\Relations\MorphTo;

interface Activity
{
    public function subject(): MorphTo;

    public function causer(): MorphTo;

    public function getProperty(string $propertyName, mixed $defaultValue = null): mixed;
}
