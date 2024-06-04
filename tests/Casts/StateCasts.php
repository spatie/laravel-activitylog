<?php

namespace Spatie\Activitylog\Test\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Test\Models\States\Pending;
use Spatie\Activitylog\Test\Models\States\PendingCompareable;
use Spatie\Activitylog\Test\Models\States\Ready;
use Spatie\Activitylog\Test\Models\States\ReadyCompareable;

class StateCasts implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        return match ($value) {
            'ready' => new Ready(),
            'pending' => new Pending(),
            'ready_compareable' => new ReadyCompareable(),
            'pending_compareable' => new PendingCompareable(),
        };
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        return $value->status_name;
    }
}
