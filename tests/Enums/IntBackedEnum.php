<?php

namespace Spatie\Activitylog\Test\Enums;

enum IntBackedEnum: int
{
    case Published = 1;
    case Draft = 0;
}
