<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;

trait DetectsChanges
{
    protected $oldAttributes = [];

    protected static function bootDetectsChanges()
    {
        if (static::eventsToBeRecorded()->contains('updated')) {
            static::updating(function (Model $model) {

                $oldValues = $model->replicate()->setRawAttributes($model->getOriginal());

                $model->oldAttributes = static::logChanges($oldValues);
            });
        }
    }

    public function attributesToBeLogged(): array
    {
        if (!isset(static::$logAttributes)) {
            return [];
        }

        return static::$logAttributes;
    }

    public function getPropertiesToBeLogged(string $processingEvent): array
    {
        if (!count($this->attributesToBeLogged())) {
            return [];
        }

        $properties['attributes'] = static::logChanges($this);

        if (static::eventsToBeRecorded()->contains('updated') && $processingEvent == 'updated') {
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);

            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);
        }

        return $properties;
    }

    public static function logChanges(Model $model): array
    {
        return collect($model)->only($model->attributesToBeLogged())->toArray();
    }
}
