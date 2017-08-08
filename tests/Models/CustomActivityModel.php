<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Models\Activity;

class CustomActivityModel extends Activity
{
    function getCustomPropertyAttribute() {
        return $this->changes();
    }
}
