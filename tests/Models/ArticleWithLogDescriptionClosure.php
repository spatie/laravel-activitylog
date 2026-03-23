<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

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
