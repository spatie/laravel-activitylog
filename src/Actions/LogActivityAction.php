<?php

namespace Spatie\Activitylog\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LogActivityAction
{
    public function execute(Model $activity, string $description): Model
    {
        $activity->description = $this->resolveDescription($activity, $description);

        $this->tapActivity($activity);

        $this->save($activity);

        return $activity;
    }

    protected function resolveDescription(Model $activity, string $description): string
    {
        return $this->replacePlaceholders(
            $activity->description ?? $description,
            $activity
        );
    }

    protected function tapActivity(Model $activity): void
    {
        if (! isset($activity->subject)) {
            return;
        }

        if (! method_exists($activity->subject, 'tapActivity')) {
            return;
        }

        call_user_func([$activity->subject, 'tapActivity'], $activity, $activity->event ?? '');
    }

    protected function save(Model $activity): void
    {
        $activity->save();
    }

    protected function replacePlaceholders(string $description, Model $activity): string
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
