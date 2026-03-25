<?php

namespace Spatie\Activitylog\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Support\ActivityBuffer;

class LogActivityAction
{
    /** @var array<Closure(Activity): void> */
    protected static array $beforeLoggingCallbacks = [];

    /** @param Closure(Activity): void $callback */
    public static function beforeLogging(Closure $callback): void
    {
        static::$beforeLoggingCallbacks[] = $callback;
    }

    public static function clearBeforeLoggingCallbacks(): void
    {
        static::$beforeLoggingCallbacks = [];
    }

    public function execute(Model $activity, string $description): Model
    {
        $activity->description = $this->resolveDescription($activity, $description);

        $this->transformChanges($activity);

        $this->beforeActivityLogged($activity);

        foreach (static::$beforeLoggingCallbacks as $callback) {
            $callback($activity);
        }

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

    protected function transformChanges(Model $activity): void
    {
        //
    }

    protected function beforeActivityLogged(Model $activity): void
    {
        if (! isset($activity->subject)) {
            return;
        }

        if (! method_exists($activity->subject, 'beforeActivityLogged')) {
            return;
        }

        $activity->subject->beforeActivityLogged($activity, $activity->event ?? '');
    }

    protected function save(Model $activity): void
    {
        if ($this->shouldBuffer()) {
            app(ActivityBuffer::class)->add($activity);

            return;
        }

        $activity->save();
    }

    protected function shouldBuffer(): bool
    {
        return config('activitylog.buffer.enabled', false);
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
