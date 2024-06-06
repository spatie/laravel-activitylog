<?php

namespace Spatie\Activitylog\Contracts;

interface Compareable
{
    public function compareTo(Compareable $compareable): int;
}
