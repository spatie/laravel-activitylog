<?php
namespace Spatie\Activitylog;

use Illuminate\Database\Eloquent\Model;

class EventLogBag
{
    public function __construct(
        public string $event,
        public Model $model,
        public array $changes,
        public ?ActivitylogOptions $options = null
    ) {
        if (is_null($this->options)) {
            $this->options = $model->getActivitylogOptions();
        }
    }
}
