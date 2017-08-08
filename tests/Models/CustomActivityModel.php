<?php

namespace Spatie\Activitylog\Test\Models;

use Spatie\Activitylog\Models\Activity;

class CustomActivityModel extends Activity
{
    public function getCustomPropertyAttribute() {
        return $this->changes();
    }
}
