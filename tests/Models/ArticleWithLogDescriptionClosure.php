<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ArticleWithLogDescriptionClosure extends Article
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(function ($eventName) {
                return $eventName;
            });
    }
}
