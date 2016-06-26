<?php

namespace Spatie\Activitylog;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;
use Spatie\Activitylog\Models\Activity;

class ActivityLogger
{
    /** @var \Illuminate\Contracts\Auth\Guard */
    protected $auth;

    /** @var \Illuminate\Database\Eloquent\Model */
    protected $performedOn;

    /** @var \Illuminate\Database\Eloquent\Model */
    protected $causedBy;

    /** @var array */
    protected $properties;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;

        $this->causedBy = $auth->user();
    }

    public function performedOn(Model $model)
    {
        $this->performedOn = $model;

        return $this;
    }

    public function on(Model $model)
    {
        return $this->performedOn($model);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|int|string $modelOrId
     *
     * @return $this
     */
    public function causedBy($modelOrId)
    {
        $model = $this->normalizeCauser($modelOrId);

        $this->causedBy = $model;

        return $this;
    }

    public function by($modelOrId)
    {
        return $this->causedBy($modelOrId);
    }

    public function withProperties(array $properties)
    {
        $this->properties = $properties;

        return $this;
    }

    public function log(string $description)
    {
        $activity = new Activity();

        $activity->description = $this->replacePlaceholders($description);

        if ($this->performedOn) {
            $activity->subject()->associate($this->performedOn);
        }

        if ($this->causedBy) {
            $activity->causer()->associate($this->causedBy);
        }

        $activity->properties = $this->properties;

        $activity->save();
    }

    protected function normalizeCauser($modelOrId): Model
    {
        if ($modelOrId instanceof Model) {
            return $modelOrId;
        }

        if ($model = $this->auth->getProvider()->retrieveById($modelOrId)) {
            return $model;
        }

        throw CouldNotLogActivity::couldNotDetermineUser($modelOrId);
    }

    protected function replacePlaceholders(string $description): string
    {
        return preg_replace_callback('/:[a-z._]+/i', function ($match) {
            return array_get($this->properties, substr($match[0], 1), $match[0]);
        }, $description);
    }
}
