<?php

namespace Spatie\Activitylog\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

interface Activity
{
    public function subject(): MorphTo;

    public function causer(): MorphTo;

    public function getExtraProperty(string $propertyName);

    public function changes(): Collection;

    public function scopeInLog(Builder $query, ...$logNames): Builder;

    public function scopeCausedBy(Builder $query, Model $causer): Builder;

    public function scopeForSubject(Builder $query, Model $subject): Builder;
}
