<?php

namespace Spatie\Activitylog\Test\Models\States;

class Pending extends State
{
    public function __construct()
    {
        $this->status_name = 'pending';
    }
}
