<?php

namespace Spatie\Activitylog\Test\Models\States;

use Spatie\Activitylog\Contracts\Compareable;

class ReadyCompareable extends State implements Compareable
{
    public function __construct()
    {
        $this->status_name = 'ready_compareable';
    }

    public function compareTo(Compareable $compareable): int
    {
        return $this->other_field === $compareable->other_field ? 0 : 1;
    }
}
