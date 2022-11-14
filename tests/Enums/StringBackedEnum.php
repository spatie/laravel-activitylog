<?php

namespace Spatie\Activitylog\Test\Enums;

enum StringBackedEnum: string
{
    case Published = 'published';
    case Draft = 'draft';
}
