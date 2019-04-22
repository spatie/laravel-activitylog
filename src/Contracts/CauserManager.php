<?php

namespace Spatie\Activitylog\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CauserManager
{
    public function getCauser($modelOrId);

    public function getDefaultCauser();

}
