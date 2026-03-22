<?php

namespace Spatie\Activitylog;

enum ActivityEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
}
