<?php

namespace Spatie\Activitylog;

use Spatie\Activitylog\Actions\CleanActivityLogAction;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Exceptions\InvalidConfiguration;

class ActivitylogConfig
{
    public static function logActivityAction(): LogActivityAction
    {
        $actionClass = config('activitylog.actions.log_activity') ?? LogActivityAction::class;

        if (! is_a($actionClass, LogActivityAction::class, true)) {
            throw InvalidConfiguration::actionIsNotValid($actionClass, LogActivityAction::class);
        }

        return app($actionClass);
    }

    public static function cleanActivityLogAction(): CleanActivityLogAction
    {
        $actionClass = config('activitylog.actions.clean_log') ?? CleanActivityLogAction::class;

        if (! is_a($actionClass, CleanActivityLogAction::class, true)) {
            throw InvalidConfiguration::actionIsNotValid($actionClass, CleanActivityLogAction::class);
        }

        return app($actionClass);
    }
}
