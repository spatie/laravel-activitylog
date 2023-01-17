<?php

namespace Spatie\Activitylog;

use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class ActivityLogger
{
    use Conditionable;
    use Macroable;

    protected ?string $defaultLogName = null;

    protected CauserResolver $causerResolver;

    protected ActivityLogStatus $logStatus;

    protected ?ActivityContract $activity = null;

    protected LogBatch $batch;

    public function __construct(Repository $config, ActivityLogStatus $logStatus, LogBatch $batch, CauserResolver $causerResolver)
    {
        $this->causerResolver = $causerResolver;

        $this->batch = $batch;

        $this->defaultLogName = $config['activitylog']['default_log_name'];

        $this->logStatus = $logStatus;
    }

    public function setLogStatus(ActivityLogStatus $logStatus): static
    {
        $this->logStatus = $logStatus;

        return $this;
    }

    public function performedOn(Model $model): static
    {
        $this->getActivity()->subject()->associate($model);

        return $this;
    }

    public function on(Model $model): static
    {
        return $this->performedOn($model);
    }

    public function causedBy(Model | int | string | null $modelOrId): static
    {
        if ($modelOrId === null) {
            return $this;
        }

        $model = $this->causerResolver->resolve($modelOrId);

        $this->getActivity()->causer()->associate($model);

        return $this;
    }

    public function by(Model | int | string | null $modelOrId): static
    {
        return $this->causedBy($modelOrId);
    }

    public function causedByAnonymous(): static
    {
        $this->activity->causer_id = null;
        $this->activity->causer_type = null;

        return $this;
    }

    public function byAnonymous(): static
    {
        return $this->causedByAnonymous();
    }

    public function event(string $event): static
    {
        return $this->setEvent($event);
    }

    public function setEvent(string $event): static
    {
        $this->activity->event = $event;

        return $this;
    }

    public function withProperties(mixed $properties): static
    {
        $this->getActivity()->properties = collect($properties);

        return $this;
    }

    public function withProperty(string $key, mixed $value): static
    {
        $this->getActivity()->properties = $this->getActivity()->properties->put($key, $value);

        return $this;
    }

    public function createdAt(DateTimeInterface $dateTime): static
    {
        $this->getActivity()->created_at = Carbon::instance($dateTime);

        return $this;
    }

    public function useLog(?string $logName): static
    {
        $this->getActivity()->log_name = $logName;

        return $this;
    }

    public function inLog(?string $logName): static
    {
        return $this->useLog($logName);
    }

    public function tap(callable $callback, string $eventName = null): static
    {
        call_user_func($callback, $this->getActivity(), $eventName);

        return $this;
    }

    public function enableLogging(): static
    {
        $this->logStatus->enable();

        return $this;
    }

    public function disableLogging(): static
    {
        $this->logStatus->disable();

        return $this;
    }

    public function log(string $description): ?ActivityContract
    {
        if ($this->logStatus->disabled()) {
            return null;
        }

        $activity = $this->activity;

        $activity->description = $this->replacePlaceholders(
            $activity->description ?? $description,
            $activity
        );

        if (isset($activity->subject) && method_exists($activity->subject, 'tapActivity')) {
            $this->tap([$activity->subject, 'tapActivity'], $activity->event ?? '');
        }

        $activity->save();

        $this->activity = null;

        return $activity;
    }

    public function withoutLogs(Closure $callback): mixed
    {
        if ($this->logStatus->disabled()) {
            return $callback();
        }

        $this->logStatus->disable();

        try {
            return $callback();
        } finally {
            $this->logStatus->enable();
        }
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

    protected function getActivity(): ActivityContract
    {
        if (! $this->activity instanceof ActivityContract) {
            $this->activity = ActivitylogServiceProvider::getActivityModelInstance();
            $this
                ->useLog($this->defaultLogName)
                ->withProperties([])
                ->causedBy($this->causerResolver->resolve());

            $this->activity->batch_uuid = $this->batch->getUuid();
        }

        return $this->activity;
    }
}
