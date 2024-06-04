<?php

namespace Spatie\Activitylog\Test\Models\States;

class Ready extends State
{
    public function __construct()
    {
        $this->status_name = 'ready';
    }
}
