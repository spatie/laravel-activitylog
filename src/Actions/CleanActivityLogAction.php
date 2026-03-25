<?php

namespace Spatie\Activitylog\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Support\Config;

class CleanActivityLogAction
{
    public function execute(int $maxAgeInDays, ?string $logName = null): int
    {
        $cutOffDate = $this->getCutOffDate($maxAgeInDays);

        return $this->deleteOldActivities($cutOffDate, $logName);
    }

    protected function getCutOffDate(int $maxAgeInDays): string
    {
        return Carbon::now()->subDays($maxAgeInDays)->format('Y-m-d H:i:s');
    }

    protected function deleteOldActivities(string $cutOffDate, ?string $logName): int
    {
        $activity = Config::activityModelInstance();

        return $activity::query()
            ->where('created_at', '<', $cutOffDate)
            ->when($logName !== null, function (Builder $query) use ($logName) {
                $query->inLog($logName);
            })
            ->delete();
    }
}
