<?php

namespace Spatie\Activitylog\Actions;

use Illuminate\Support\Str;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class LogActivityAction
{
    public function execute(ActivityContract $activity, string $description): ActivityContract
    {
        $activity->description = $this->resolveDescription($activity, $description);

        $this->tapActivity($activity);

        $this->save($activity);

        return $activity;
    }

    protected function resolveDescription(ActivityContract $activity, string $description): string
    {
        return $this->replacePlaceholders(
            $activity->description ?? $description,
            $activity
        );
    }

    protected function tapActivity(ActivityContract $activity): void
    {
        if (! isset($activity->subject)) {
            return;
        }

        if (! method_exists($activity->subject, 'tapActivity')) {
            return;
        }

        call_user_func([$activity->subject, 'tapActivity'], $activity, $activity->event ?? '');
    }

    protected function save(ActivityContract $activity): void
    {
        $activity->save();
    }

    protected function replacePlaceholders(string $description, ActivityContract $activity): string
    {
        return preg_replace_callback('/:[a-z0-9._-]+(?<![.])/i', function ($match) use ($activity) {
            $match = $match[0];

            $attribute = Str::before(Str::after($match, ':'), '.');

            if (! in_array($attribute, ['subject', 'causer', 'properties'])) {
                return $match;
            }

            $propertyName = substr($match, strpos($match, '.') + 1);

            $attributeValue = $activity->$attribute;

            if (is_null($attributeValue)) {
                return $match;
            }

            return data_get($attributeValue, $propertyName, $match);
        }, $description);
    }
}
