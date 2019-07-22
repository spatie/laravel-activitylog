<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Models\Activity;

class CustomDatabaseConnectionOnActivityModel extends Activity
{
    protected $connection = 'custom_connection_name';
}
