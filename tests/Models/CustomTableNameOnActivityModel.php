<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Models\Activity;

class CustomTableNameOnActivityModel extends Activity
{
    protected $table = 'custom_table_name';
}
