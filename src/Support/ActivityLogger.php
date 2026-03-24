<?php

namespace Spatie\Activitylog\Support;

use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Enums\ActivityEvent;

class ActivityLogger
{
    use Conditionable;
    use Macroable;

    protected ?string $defaultLogName = null;

    protected CauserResolver $causerResolver;

    protected ActivityLogStatus $logStatus;

    protected ?ActivityContract $activity = null;

    public function __construct(Repository $config, ActivityLogStatus $logStatus, CauserResolver $causerResolver)
    {
        $this->causerResolver = $causerResolver;

        $this->defaultLogName = $config->get('activitylog.default_log_name');

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

    public function causedBy(Model|int|string|null $modelOrId): static
    {
        if ($modelOrId === null) {
            return $this;
        }

        $model = $this->causerResolver->resolve($modelOrId);

        $this->getActivity()->causer()->associate($model);

        return $this;
    }

    public function by(Model|int|string|null $modelOrId): static
    {
        return $this->causedBy($modelOrId);
    }

    public function causedByAnonymous(): static
    {
        $activity = $this->getActivity();

        $activity->causer_id = null;
        $activity->causer_type = null;

        return $this;
    }

    public function byAnonymous(): static
    {
        return $this->causedByAnonymous();
    }

    public function event(string|ActivityEvent $event): static
    {
        return $this->setEvent($event);
    }

    public function setEvent(string|ActivityEvent $event): static
    {
        $this->getActivity()->event = $event instanceof ActivityEvent ? $event->value : $event;

        return $this;
    }

    /** @param  array<string, mixed>|Collection<string, mixed>  $changes */
    public function withChanges(array|Collection $changes): static
    {
        $this->getActivity()->attribute_changes = collect($changes);

        return $this;
    }

    /** @param  array<string, mixed>|Collection<string, mixed>  $properties */
    public function withProperties(array|Collection $properties): static
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

    public function tap(callable $callback, ?string $eventName = null): static
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

        $activity = Config::logActivityAction()->execute(
            $this->getActivity(),
            $description,
        );

        $this->activity = null;

        return $activity;
    }

    public function withoutLogging(Closure $callback): mixed
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

    protected function getActivity(): ActivityContract
    {
        if (! $this->activity instanceof ActivityContract) {
            $this->activity = Config::activityModelInstance();
            $this
                ->useLog($this->defaultLogName)
                ->withChanges([])
                ->withProperties([])
                ->causedBy($this->causerResolver->resolve());
        }

        return $this->activity;
    }
}
