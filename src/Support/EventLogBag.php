<?php

namespace Spatie\Activitylog\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Enums\ActivityEvent;

class EventLogBag
{
    public function __construct(
        public string | ActivityEvent $event,
        public Model $model,
        public array $changes,
        public ?LogOptions $options = null
    ) {
        $this->options ??= $model->getActivitylogOptions();
    }
}
