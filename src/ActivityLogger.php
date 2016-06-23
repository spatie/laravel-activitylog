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
    protected $extraProperties;
    
    public function __construct(Guard $auth)
    {
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
     */
    public function causedBy($modelOrId)
    {
        $model = $this->normalizeCauser($modelOrId);

        $this->causedBy = $model;
    }
    
    public function by($modelOrId)
    {
        return $this->causedBy($modelOrId);
    }
    
    public function withExtraProperties(array $extraProperties)
    {
        $this->extraProperties = $extraProperties;
        
        return $this;
    }
    
    public function log(string $description)
    {
        $activity = new Activity();

        $activity->description = $description;
        $activity->subject()->associate($this->performedOn);
        $activity->causer()->associate($this->causedBy);
        $activity->extra_properties = $this->extraProperties;

        $activity->save();
    }

    protected function normalizeCauser($modelOrId): Model
    {
        if ($modelOrId instanceof Model) {
            return $modelOrId;
        }

        if ($model = $this->auth->retrieveById($modelOrId)) {
            return $model;
        }

        throw CouldNotLogActivity::couldNotDetermineUser($modelOrId);
    }
}
