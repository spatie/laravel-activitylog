<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\ActivityLogger;

trait DetectsChanges
{
    protected $oldValues = [];

    protected $newValues = [];

    protected static function bootDetectsChanges()
    {
        
    }
}
