<?php

namespace Spatie\Activitylog\Support;

use BackedEnum;
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

    protected bool $causedByAnonymous = false;

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

        $this->causedByAnonymous = true;

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

    public function useLog(BackedEnum|string|null $logName): static
    {
        $this->getActivity()->log_name = $logName instanceof BackedEnum ? $logName->value : $logName;

        return $this;
    }

    public function inLog(BackedEnum|string|null $logName): static
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

        $this->useDefaultCauserIfNoneWasGiven();

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
            $this->causedByAnonymous = false;
            $this
                ->useLog($this->defaultLogName)
                ->withChanges([])
                ->withProperties([]);
        }

        return $this->activity;
    }

    /**
     * The default causer is resolved when the activity is logged, not when the logger is built,
     * so resolving the logger (e.g. through the facade in a service provider) doesn't touch auth or the session.
     */
    protected function useDefaultCauserIfNoneWasGiven(): void
    {
        $activity = $this->getActivity();

        if ($this->causedByAnonymous || ($activity instanceof Model && $activity->getAttribute('causer_id') !== null)) {
            return;
        }

        $this->causedBy($this->causerResolver->resolve());
    }
}
