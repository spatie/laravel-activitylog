<?php

namespace Spatie\Activitylog;

use Closure;

class ActivitylogOptions
{
    public ?string $logName;

    public bool $submitEmptyLogs = true;

    public bool $logFillable = false;

    public bool $logOnlyDirty = false;

    public bool $logUnguarded = false;

    public array $logAttributes = [];

    public array $ignoredAttributes = [];

    public array $dontLogIfAttributesChangedBag = [];

    public ?Closure $descriptionForEvent;

    public static function create(): self
    {
        return new static;
    }

    public static function defaults(): self
    {
        return static::create();
    }


    public function logAll(): self
    {
        return $this->logOnly(['*']);
    }

    public function logUnguarded(): self
    {
        $this->logUnguarded = true;

        return $this;
    }

    public function logFillable(): self
    {
        $this->logFillable = true;

        return $this;
    }

    public function dontLogFillable(): self
    {
        $this->logFillable = false;

        return $this;
    }


    public function logOnlyDirty(): self
    {
        $this->logOnlyDirty = true;

        return $this;
    }


    public function logOnly(array $attributes): self
    {
        $this->logAttributes = $attributes;

        return $this;
    }


    public function ignore(array $attributes): self
    {
        $this->ignoredAttributes = $attributes;

        return $this;
    }



    public function dontLogIfAttributesChanged(array $attributes): self
    {
        $this->dontLogIfAttributesChangedBag = $attributes;

        return $this;
    }


    public function DontSubmitEmptyLogs(): self
    {
        $this->submitEmptyLogs = false;

        return $this;
    }

    public function disableLogging(): self
    {
        $this->enableLoggingModelsEvents = false;

        return $this;
    }

    public function enableLogging(): self
    {
        $this->enableLoggingModelsEvents = true;

        return $this;
    }

    public function useLogName(string $logname): self
    {
        $this->logName = $logname;

        return $this;
    }


    public function setDescriptionForEvent(Closure $callback): self
    {
        $this->descriptionForEvent = $callback;

        return $this;
    }
}
