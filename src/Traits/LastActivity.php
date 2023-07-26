<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\Models\Activity;

trait LastActivity
{
    public function lastActivity(): BelongsTo
    {
        return $this->belongsTo(ActivitylogServiceProvider::determineActivityModel());
    }

    public function scopeWithLastActivity(Builder $query, ?string $event = null)
    {
        $query->addSelect(['last_activity_id' => Activity::select('id')
            ->where('subject_type', '=', self::class)
            ->whereColumn('subject_id', self::getTable().'.id')
            ->when($event, function (Builder $query) use ($event) {
                $query->where('event', '=', $event);
            })
            ->latest()
            ->take(1),
        ])->with('lastActivity.causer');
    }
}
