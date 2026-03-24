<?php

namespace Spatie\Activitylog\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Enums\ActivityEvent;

/**
 * @property int $id
 * @property string|null $log_name
 * @property string $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property string|null $event
 * @property Collection|null $attribute_changes
 * @property Collection|null $properties
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|null $causer
 * @property-read Model|null $subject
 */
class Activity extends Model implements ActivityContract
{
    public $guarded = [];

    protected function casts(): array
    {
        return [
            'attribute_changes' => 'collection',
            'properties' => 'collection',
        ];
    }

    protected $table = 'activity_log';

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        if (config('activitylog.include_soft_deleted_subjects')) {
            return $this->morphTo()->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function getProperty(string $propertyName, mixed $defaultValue = null): mixed
    {
        return Arr::get($this->properties?->toArray() ?? [], $propertyName, $defaultValue);
    }

    /** @param  string|string[]  ...$logNames */
    public function scopeInLog(Builder $query, string|array ...$logNames): Builder
    {
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('log_name', $logNames);
    }

    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    public function scopeForEvent(Builder $query, string|ActivityEvent $event): Builder
    {
        return $query->where('event', $event instanceof ActivityEvent ? $event->value : $event);
    }
}
