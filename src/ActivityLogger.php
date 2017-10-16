<?php

namespace Spatie\Activitylog;

use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Config\Repository;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;

class ActivityLogger
{
    use Macroable;

    /**
     * The authmanager instance.
     *
     * @var \Illuminate\Auth\AuthManager
     */
    protected $auth;

    /**
     * The authenticator driver.
     *
     * @var string
     */
    protected $authDriver;

    /**
     * The name of the log.
     *
     * @var string
     */
    protected $logName = '';

    /**
     * Whether or not the log is enabled.
     *
     * @var bool
     */
    protected $logEnabled;

    /**
     * The model this activity was logged on.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $performedOn;

    /**
     * The model this activity was caused by.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $causedBy;

    /**
     * The additional properies to log with the activity.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $properties;

    /**
     * @param \Illuminate\Auth\AuthManager $auth
     * @param Illuminate\Contracts\Config\Repository $config
     * @return null
     */
    public function __construct(AuthManager $auth, Repository $config)
    {
        $this->auth = $auth;

        $this->properties = collect();

        $this->authDriver = $config['activitylog']['default_auth_driver'] ?? $auth->getDefaultDriver();

        if (starts_with(app()->version(), '5.1')) {
            $this->causedBy = $auth->driver($this->authDriver)->user();
        } else {
            $this->causedBy = $auth->guard($this->authDriver)->user();
        }

        $this->logName = $config['activitylog']['default_log_name'];

        $this->logEnabled = $config['activitylog']['enabled'] ?? true;
    }

    /**
     * Sets the model this activity was performed on.
     *
     * @param \Illuminate\Database\Eloquent\Model
     * @return $this
     */
    public function performedOn(Model $model)
    {
        $this->performedOn = $model;

        return $this;
    }

    /**
     * Sets the model this activity was performed on.
     *
     * @param \Illuminate\Database\Eloquent\Model
     * @return $this
     */
    public function on(Model $model)
    {
        return $this->performedOn($model);
    }

    /**
     * Sets the model this activity was caused by.
     *
     * @param \Illuminate\Database\Eloquent\Model|int|string $modelOrId
     * @return $this
     */
    public function causedBy($modelOrId)
    {
        $model = $this->normalizeCauser($modelOrId);

        $this->causedBy = $model;

        return $this;
    }

    /**
     * Sets the model this activity was caused by.
     *
     * @param \Illuminate\Database\Eloquent\Model|int|string $modelOrId
     * @return $this
     */
    public function by($modelOrId)
    {
        return $this->causedBy($modelOrId);
    }

    /**
     * Define the additional properties that relates to this activity.
     *
     * @param array|\Illuminate\Support\Collection $properties
     * @return $this
     */
    public function withProperties($properties)
    {
        $this->properties = collect($properties);

        return $this;
    }

    /**
     * Adds an additional property that relates to this activity.
     *
     * @param string $key
     * @param mixed  $value
     * @return $this
     */
    public function withProperty(string $key, $value)
    {
        $this->properties->put($key, $value);

        return $this;
    }

    /**
     * Sets the logs name.
     *
     * @param string $logName
     * @return $this
     */
    public function useLog(string $logName)
    {
        $this->logName = $logName;

        return $this;
    }

    /**
     * Sets the log name.
     *
     * @param string $logName
     * @return $this
     */
    public function inLog(string $logName)
    {
        return $this->useLog($logName);
    }

    /**
     * Persists this activity.
     *
     * @param string $description
     * @return null|mixed
     */
    public function log(string $description)
    {
        if (! $this->logEnabled) {
            return;
        }

        $activity = ActivitylogServiceProvider::getActivityModelInstance();

        if ($this->performedOn) {
            $activity->subject()->associate($this->performedOn);
        }

        if ($this->causedBy) {
            $activity->causer()->associate($this->causedBy);
        }

        $activity->properties = $this->properties;

        $activity->description = $this->replacePlaceholders($description, $activity);

        $activity->log_name = $this->logName;

        $activity->save();

        return $activity;
    }

    /**
     * Finds the Model by either ID or Model instance.
     *
     * @param \Illuminate\Database\Eloquent\Model|int|string $modelOrId
     * @throws \Spatie\Activitylog\Exceptions\CouldNotLogActivity
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function normalizeCauser($modelOrId): Model
    {
        if ($modelOrId instanceof Model) {
            return $modelOrId;
        }

        if (starts_with(app()->version(), '5.1')) {
            $model = $this->auth->driver($this->authDriver)->getProvider()->retrieveById($modelOrId);
        } else {
            $model = $this->auth->guard($this->authDriver)->getProvider()->retrieveById($modelOrId);
        }

        if ($model) {
            return $model;
        }

        throw CouldNotLogActivity::couldNotDetermineUser($modelOrId);
    }

    /**
     * Formats the activity message against a pattern.
     *
     * @param string $description
     * @param \Spatie\Activitylog\Models\Activity $activity
     * @return string
     */
    protected function replacePlaceholders(string $description, Activity $activity): string
    {
        return preg_replace_callback('/:[a-z0-9._-]+/i', function ($match) use ($activity) {
            $match = $match[0];

            $attribute = (string) string($match)->between(':', '.');

            if (! in_array($attribute, ['subject', 'causer', 'properties'])) {
                return $match;
            }

            $propertyName = substr($match, strpos($match, '.') + 1);

            $attributeValue = $activity->$attribute;

            if (is_null($attributeValue)) {
                return $match;
            }

            $attributeValue = $attributeValue->toArray();

            return array_get($attributeValue, $propertyName, $match);
        }, $description);
    }
}
