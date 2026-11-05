<?php

namespace Spatie\Activitylog\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ActivityBuffer
{
    /** @var array<int, array<string, mixed>> */
    protected array $pending = [];

    public function add(Model $activity): void
    {
        $this->pending[] = $this->prepareForInsert($activity);
    }

    public function flush(): void
    {
        if (empty($this->pending)) {
            return;
        }

        $records = collect($this->pending)->map(function (array $record) {
            ksort($record);

            return $record;
        });

        $modelClass = Config::activityModel();

        $modelClass::query()->getConnection()->transaction(function () use ($modelClass, $records) {
            $records
                ->chunkWhile(fn (array $record, int $key, Collection $chunk) => array_keys($record) === array_keys($chunk->last()))
                ->each(fn (Collection $chunk) => $modelClass::query()->insert($chunk->all()));
        });

        $this->pending = [];
    }

    public function hasPending(): bool
    {
        return ! empty($this->pending);
    }

    public function count(): int
    {
        return count($this->pending);
    }

    /** @return array<string, mixed> */
    protected function prepareForInsert(Model $activity): array
    {
        $now = $activity->freshTimestampString();
        $attributes = [];

        foreach ($activity->getAttributes() as $key => $value) {
            if ($value instanceof Collection) {
                $attributes[$key] = $value->toJson();
            } elseif ($value instanceof \DateTimeInterface) {
                $attributes[$key] = $activity->fromDateTime($value);
            } else {
                $attributes[$key] = $value;
            }
        }

        $attributes['created_at'] ??= $now;
        $attributes['updated_at'] ??= $now;

        return $attributes;
    }
}
