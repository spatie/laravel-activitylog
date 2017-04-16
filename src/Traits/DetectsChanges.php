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

    public function shouldLogDirtyOnly(): bool
    {
        if (! isset(static::$logDirtyOnly)) {
            return false;
        }

        return static::$logDirtyOnly;
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

        // Only dirty fields
        if (isset($properties['old']) && $this->shouldLogDirtyOnly()) {
            $properties['attributes'] = collect($properties['attributes'])->diff($properties['old'])->all();
            $properties['old'] = collect($properties['old'])->only(array_keys($properties['attributes']))->all();
        }

        return $properties;
    }

    public static function logChanges(Model $model): array
    {
        return collect($model->attributesToBeLogged())->mapWithKeys(
            function ($attribute) use ($model) {
                if (str_contains($attribute, '.')) {
                    return self::getRelatedModelAttributeValue($model, $attribute);
                }

                return collect($model)->only($attribute);
            }
        )->toArray();
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
}
