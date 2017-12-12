<?php

namespace Spatie\Activitylog\Traits;

use Carbon\Carbon;
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
        $attributes = [];

        if (isset(static::$logFillable)) {
            $attributes = array_merge($attributes, $this->fillable);
        }

        if (isset(static::$logAttributes)) {
            if (in_array('*', static::$logAttributes)) {
                $withoutWildcard = array_diff(static::$logAttributes, ['*']);

                $attributes = array_merge($attributes, array_keys($this->attributes), $withoutWildcard);
            } else {
                $attributes = array_merge($attributes, static::$logAttributes);
            }
        }

        return $attributes;
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

        $properties['attributes'] = static::logChanges(
            $this->exists
                ? $this->fresh() ?? $this
                : $this
        );

        if (static::eventsToBeRecorded()->contains('updated') && $processingEvent == 'updated') {
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);

            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);
        }

        if ($this->shouldlogOnlyDirty() && isset($properties['old'])) {
            $properties['attributes'] = array_udiff_assoc(
                $properties['attributes'],
                $properties['old'],
                function ($new, $old) {
                    return $new <=> $old;
                }
            );
            $properties['old'] = collect($properties['old'])
                ->only(array_keys($properties['attributes']))
                ->all();
        }

        return $properties;
    }

    public static function logChanges(Model $model): array
    {
        $changes = [];
        foreach ($model->attributesToBeLogged() as $attribute) {
            $changes += collect([$attribute => self::getModelAttributeValue($model, $attribute)])
                ->map(function($value) {
                    return $value instanceof Carbon ? $value->__toString() : $value;
                })
                ->toArray();
        }

        return $changes;
    }

    protected static function getModelAttributeValue(Model $model, string $attribute)
    {
        if (str_contains($attribute, '.')) {
            return self::getRelatedModelAttributeValue($model, $attribute);
        } elseif(in_array($attribute, $model->getHidden()) && isset($model::$logHidden) && $model::$logHidden) {
            if(isset($model::$logHiddenObfuscated) && $model::$logHiddenObfuscated) {
                return config('activitylog.hidden_obfuscation');
            }

            return $model->getAttribute($attribute);
        } elseif(!in_array($attribute, $model->getHidden()) && array_key_exists($attribute, $model->getAttributes())) {
            return $model->getAttribute($attribute);
        }

        return null;
    }

    protected static function getRelatedModelAttributeValue(Model $model, string $attribute)
    {
        if (substr_count($attribute, '.') > 1) {
            throw CouldNotLogChanges::invalidAttribute($attribute);
        }

        $keyParts = explode('.', $attribute);
        $relationName = array_shift($keyParts);
        $relatedAttribute = implode('.', $keyParts);

        $model->loadMissing($relationName);

        if($model->relationLoaded($relationName) && $model->$relationName instanceof Model) {
            $relatedModel = $model->$relationName;

            return self::getModelAttributeValue($relatedModel, $relatedAttribute);
        }

        return null;
    }
}
