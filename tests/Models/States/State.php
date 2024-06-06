<?php

namespace Spatie\Activitylog\Test\Models\States;

use Spatie\Activitylog\Test\Casts\StateCasts;

class State extends StateCasts
{
    public string $status_name;
    public string $other_field = 'other value';

    public function __toString(): string
    {
        return $this->status_name;
    }
}
