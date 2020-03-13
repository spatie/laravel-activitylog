<?php

namespace Spatie\Activitylog;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;

class ActivityLogger
{
    use Macroable;

    /** @var \Illuminate\Auth\AuthManager */
    protected $auth;

    protected $defaultLogName = '';

    /** @var string */
    protected $authDriver;

    /** @var \Spatie\Activitylog\ActivityLogStatus */
    protected $logStatus;

    /** @var \Spatie\Activitylog\Contracts\Activity */
    protected $activity;

    public function __construct(AuthManager $auth, Repository $config, ActivityLogStatus $logStatus)
    {
        $this->auth = $auth;

        $this->authDriver = $config['activitylog']['default_auth_driver'] ?? $auth->getDefaultDriver();

        $this->defaultLogName = $config['activitylog']['default_log_name'];

        $this->logStatus = $logStatus;
    }

    public function setLogStatus(ActivityLogStatus $logStatus)
    {
        $this->logStatus = $logStatus;

        return $this;
    }

    public function performedOn(Model $model)
    {
        $this->getActivity()->subject()->associate($model);

        return $this;
    }

    public function on(Model $model)
    {
        return $this->performedOn($model);
    }

    public function causedBy($modelOrId)
    {
        if ($modelOrId === null) {
            return $this;
        }

        $model = $this->normalizeCauser($modelOrId);

        $this->getActivity()->causer()->associate($model);

        return $this;
    }

    public function by($modelOrId)
    {
        return $this->causedBy($modelOrId);
    }

    public function causedByAnonymous()
    {
        $this->activity->causer_id = null;
        $this->activity->causer_type = null;

        return $this;
    }

    public function byAnonymous()
    {
        return $this->causedByAnonymous();
    }

    public function withProperties($properties)
    {
        $this->getActivity()->properties = collect($properties);

        return $this;
    }

    public function withProperty(string $key, $value)
    {
        $this->getActivity()->properties = $this->getActivity()->properties->put($key, $value);

        return $this;
    }

    public function createdAt(Carbon $dateTime)
    {
        $this->getActivity()->created_at = $dateTime;

        return $this;
    }

    public function useLog(string $logName)
    {
        $this->getActivity()->log_name = $logName;

        return $this;
    }

    public function inLog(string $logName)
    {
        return $this->useLog($logName);
    }

    public function tap(callable $callback, string $eventName = null)
    {
        call_user_func($callback, $this->getActivity(), $eventName);

        return $this;
    }

    public function enableLogging()
    {
        $this->logStatus->enable();

        return $this;
    }

    public function disableLogging()
    {
        $this->logStatus->disable();

        return $this;
    }

    public function log(string $description)
    {
        if ($this->logStatus->disabled()) {
            return;
        }

        $activity = $this->activity;

        $activity->description = $this->replacePlaceholders(
            $activity->description ?? $description,
            $activity
        );

        $activity->save();

        $this->activity = null;

        return $activity;
    }

    public function withoutLogs(callable $callback)
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

    protected function normalizeCauser($modelOrId): Model
    {
        if ($modelOrId instanceof Model) {
            return $modelOrId;
        }

        $guard = $this->auth->guard($this->authDriver);
        $provider = method_exists($guard, 'getProvider') ? $guard->getProvider() : null;
        $model = method_exists($provider, 'retrieveById') ? $provider->retrieveById($modelOrId) : null;

        if ($model instanceof Model) {
            return $model;
        }

        throw CouldNotLogActivity::couldNotDetermineUser($modelOrId);
    }

    protected function replacePlaceholders(string $description, ActivityContract $activity): string
    {
        return preg_replace_callback('/:[a-z0-9._-]+/i', function ($match) use ($activity) {
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

            $attributeValue = $attributeValue->toArray();

            return Arr::get($attributeValue, $propertyName, $match);
        }, $description);
    }

    protected function getActivity(): ActivityContract
    {
        if (! $this->activity instanceof ActivityContract) {
            $this->activity = ActivitylogServiceProvider::getActivityModelInstance();
            $this
                ->useLog($this->defaultLogName)
                ->withProperties([])
                ->causedBy($this->auth->guard($this->authDriver)->user());
        }

        return $this->activity;
    }
}
