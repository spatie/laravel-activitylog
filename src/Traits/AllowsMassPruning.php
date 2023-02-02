<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\MassPrunable;

trait AllowsMassPruning
{
    use MassPrunable;

    protected int $prunableMaxAgeInDays;
    protected ?string $prunableLog;

    public function configureMassPruning(int $maxAgeInDays, ?string $log = null): self
    {
        $this->prunableMaxAgeInDays = $maxAgeInDays;
        $this->prunableLog = $log;

        return $this;
    }

    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays($this->prunableMaxAgeInDays))
            ->when($this->prunableLog, fn ($query) => $query->where('log_name', $this->prunableLog));
    }
}
