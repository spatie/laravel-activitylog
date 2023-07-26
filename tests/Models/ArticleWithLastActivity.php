<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LastActivity;
use Spatie\Activitylog\Traits\LogsActivity;

class ArticleWithLastActivity extends Article
{
    use LogsActivity;
    use LastActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name']);
    }
}
