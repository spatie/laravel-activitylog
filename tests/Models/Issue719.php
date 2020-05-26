<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Traits\LogsActivity;

class Issue719 extends Article
{
    use LogsActivity;

    protected static $submitEmptyLogs = false;
    protected static $logAttributes = ['name'];
    public static $logOnlyDirty = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function (Issue719 $model): void {
            Issue719Sub::create(['name' => 'my sub model created during creation']);
        });
  }
}
