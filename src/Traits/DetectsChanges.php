<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait DetectsChanges
{
    protected $oldValues = [];

    protected $newValues = [];

    protected static function bootDetectsChanges()
    {
        collect(['creating', 'updating', 'deleting'])->each(function (string $eventName) {

            return static::$eventName(function (Model $model) use ($eventName) {

                $model->oldValues = ($eventName === 'creating' ? [] : $model->fresh()->toArray());

                $model->newValues = $model->getDirty();

            });
        });
    }

    public function getChangedAttributeNames(): array
    {
        if ($this->exists()) {
            return array_keys($this->newValues);
        }

        return array_keys($this->oldValues);

        //dd($this->oldValues, $this->newValues, array_keys(array_intersect_key($this->oldValues, $this->newValues)));
        return array_keys(array_intersect_key($this->oldValues, $this->newValues));
    }

    public function getChangedAttributes(string $eventName): Collection
    {
        if ($eventName === 'creating') {
            collect($this->getDirty())
                ->filter(function (string $attributeName) {
                    return collect($this->logChangesOnAttributes)->contains($attributeName);
                });
        }

        $changes = collect($this->getChangedAttributeNames())
            ->filter(function (string $attributeName) {
                return collect($this->logChangesOnAttributes)->contains($attributeName);
            })
            ->reduce(function (array $changes, string $changedAttributeName) {
                $changes['old'][$changedAttributeName] = $this->oldValues[$changedAttributeName] ?? null;

                $changes['attributes'][$changedAttributeName] = $this->newValues[$changedAttributeName] ?? null;

                return $changes;
            }, ['old' => [], 'attributes' => []]);
        return collect($changes);
    }
}
