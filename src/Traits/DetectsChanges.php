<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Exceptions\CouldNotLogChanges;

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

        return static::$logAttributes;
    }

    public function shouldlogOnlyDirty(): bool
    {
        if (! isset(static::$logOnlyDirty)) {
            return false;
        }

        return static::$logOnlyDirty;
    }

    public function attributeValuesToBeLogged(string $processingEvent): array
    {
        if (! count($this->attributesToBeLogged())) {
            return [];
        }

        $properties['attributes'] = static::logChanges($this->exists ? $this->fresh() : $this);

        if (static::eventsToBeRecorded()->contains('updated') && $processingEvent == 'updated') {
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);

            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);
        }

        if ($this->shouldlogOnlyDirty() && isset($properties['old'])) {
            $properties['attributes'] = $this->array_diff_assoc_recursive(
                                            $properties['attributes'],
                                            $properties['old']
                                        );
            $properties['old'] = collect($properties['old'])->only(array_keys($properties['attributes']))->all();
        }

        return $properties;
    }

    public static function logChanges(Model $model): array
    {
        $changes = [];
        foreach ($model->attributesToBeLogged() as $attribute) {
            if (str_contains($attribute, '.')) {
                $changes += self::getRelatedModelAttributeValue($model, $attribute);
            } else {
                $changes += collect($model)->only($attribute)->toArray();
            }
        }

        return $changes;
    }

    protected static function getRelatedModelAttributeValue(Model $model, string $attribute): array
    {
        if (substr_count($attribute, '.') > 1) {
            throw CouldNotLogChanges::invalidAttribute($attribute);
        }

        list($relatedModelName, $relatedAttribute) = explode('.', $attribute);

        $relatedModel = $model->$relatedModelName ?? $model->$relatedModelName();

        return ["{$relatedModelName}.{$relatedAttribute}" => $relatedModel->$relatedAttribute];
    }


    function array_diff_assoc_recursive($array1, $array2) {
        $difference = array();
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!array_key_exists($key, $array2) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->array_diff_assoc_recursive($value, $array2[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }
}
