<?php

namespace Spatie\Activitylog;

use Illuminate\Contracts\Config\Repository;

class ActivityLogStatus
{
    protected bool $enabled;

    public function __construct(Repository $config)
    {
        $this->enabled = (bool) ($config['activitylog.enabled'] ?? true);
    }

    public function enable(): bool
    {
        return $this->enabled = true;
    }

    public function disable(): bool
    {
        return $this->enabled = false;
    }

    public function disabled(): bool
    {
        return $this->enabled === false;
    }
}
