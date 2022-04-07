<?php

namespace Spatie\Activitylog;

use Closure;

class LogOptions
{
    public ?string $logName = null;

    public bool $submitEmptyLogs = true;

    public bool $logFillable = false;

    public bool $logOnlyDirty = false;

    public bool $logUnguarded = false;

    public array $logAttributes = [];

    public array $logExceptAttributes = [];

    public array $dontLogIfAttributesChangedOnly = [];

    public array $attributeRawValues = [];

    public ?Closure $descriptionForEvent = null;

    /**
     * Start configuring model with the default options.
     */
    public static function defaults(): self
    {
        return new static();
    }

    /**
     * Log all attributes on the model.
     */
    public function logAll(): self
    {
        return $this->logOnly(['*']);
    }

    /**
     * Log all attributes that are not listed in $guarded.
     */
    public function logUnguarded(): self
    {
        $this->logUnguarded = true;

        return $this;
    }

    /**
     * log changes to all the $fillable attributes of the model.
     */
    public function logFillable(): self
    {
        $this->logFillable = true;

        return $this;
    }

    /**
     * Stop logging $fillable attributes of the model.
     */
    public function dontLogFillable(): self
    {
        $this->logFillable = false;

        return $this;
    }

    /**
     * Log changes that has actually changed after the update.
     */
    public function logOnlyDirty(): self
    {
        $this->logOnlyDirty = true;

        return $this;
    }

    /**
     * Log changes only if these attributes changed.
     */
    public function logOnly(array $attributes): self
    {
        $this->logAttributes = $attributes;

        return $this;
    }

    /**
     * Exclude these attributes from being logged.
     */
    public function logExcept(array $attributes): self
    {
        $this->logExceptAttributes = $attributes;

        return $this;
    }

    /**
     * Don't trigger an activity if these attributes changed logged.
     */
    public function dontLogIfAttributesChangedOnly(array $attributes): self
    {
        $this->dontLogIfAttributesChangedOnly = $attributes;

        return $this;
    }

    /**
     * Dont store empty logs. Storing empty logs can happen when you only
     * want to log a certain attribute but only another changes.
     */
    public function dontSubmitEmptyLogs(): self
    {
        $this->submitEmptyLogs = false;

        return $this;
    }

    /**
     * Allow storing empty logs. Storing empty logs can happen when you only
     * want to log a certain attribute but only another changes.
     */
    public function submitEmptyLogs(): self
    {
        $this->submitEmptyLogs = true;

        return $this;
    }

    /**
     * Customize log name.
     */
    public function useLogName(?string $logName): self
    {
        $this->logName = $logName;

        return $this;
    }

    /**
     * Customize log description using callback.
     */
    public function setDescriptionForEvent(Closure $callback): self
    {
        $this->descriptionForEvent = $callback;

        return $this;
    }

    /**
     * Exclude these attributes from being casted.
     */
    public function useAttributeRawValues(array $attributes): self
    {
        $this->attributeRawValues = $attributes;

        return $this;
    }
}
