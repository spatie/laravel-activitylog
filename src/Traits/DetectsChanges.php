<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait DetectsChanges
{
    protected $oldAttributes = [];

    protected static function bootDetectsChanges()
    {
        if (static::eventsToBeRecorded()->contains('updated')) {
            static::updating(function (Model $model) {

                //temporary hold the original attributes on the model
                //as we'll need these in the updating event
                $oldValues = $model->replicate()->setRawAttributes($model->getOriginal());

                $model->oldAttributes = static::logChanges($oldValues);
            });
        }
    }

    public function attributesToBeLogged(): array
    {
        if (! isset(static::$logAttributes)) {
            return [];
        }

        return collect(static::$logAttributes)->map(
            function (String $value) {
                if (strpos($value, '.') != 0) {
                    return explode('.', $value);
                }

                return $value;
            }
        )->toArray();
    }

    public function attributeValuesToBeLogged(string $processingEvent): array
    {
        if (! count($this->attributesToBeLogged())) {
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
        return collect($model->attributesToBeLogged())->mapWithKeys(
            function ($value) use ($model) {
                if ($value instanceof Collection) {
                    foreach ($value as $methodCall) {
                        $model = $model->$methodCall;
                    }

                    return [implode('.', $value) => $model];
                }

                return collect($model)->only($value);
            }
        )->toArray();
    }
}
