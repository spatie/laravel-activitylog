<?php

namespace Spatie\Activitylog\Support;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;

class LogOptions
{
    public ?string $logName = null;

    public bool $logEmptyChanges = true;

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
        return new self;
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
     * Only log changes to these specific attributes.
     *
     * @param  string[]  $attributes
     */
    public function logOnly(array $attributes): self
    {
        $this->logAttributes = $attributes;

        return $this;
    }

    /**
     * Exclude these attributes from being logged.
     *
     * @param  string[]  $attributes
     */
    public function logExcept(array $attributes): self
    {
        $this->logExceptAttributes = $attributes;

        return $this;
    }

    /**
     * Don't trigger an activity if only these attributes changed.
     *
     * @param  string[]  $attributes
     */
    public function dontLogIfAttributesChangedOnly(array $attributes): self
    {
        $this->dontLogIfAttributesChangedOnly = $attributes;

        return $this;
    }

    /**
     * Don't store empty logs. Empty logs can occur when you're tracking
     * specific attributes but none of them actually changed.
     */
    public function dontLogEmptyChanges(): self
    {
        $this->logEmptyChanges = false;

        return $this;
    }

    /**
     * Allow storing empty logs.
     */
    public function logEmptyChanges(): self
    {
        $this->logEmptyChanges = true;

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
     *
     * @param  string[]  $attributes
     */
    public function useAttributeRawValues(array $attributes): self
    {
        $this->attributeRawValues = $attributes;

        return $this;
    }

    public function __serialize(): array
    {
        $data = get_object_vars($this);

        if ($data['descriptionForEvent'] !== null) {
            $data['descriptionForEvent'] = new SerializableClosure($data['descriptionForEvent']);
        }

        return $data;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'descriptionForEvent' && $value instanceof SerializableClosure) {
                $value = $value->getClosure();
            }

            $this->$key = $value;
        }
    }
}
