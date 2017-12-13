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
        $attributes = [];

        if (isset(static::$logFillable) && static::$logFillable) {
            $attributes = array_merge($attributes, $this->fillable);
        }

        if (isset(static::$logAttributes) && is_array(static::$logAttributes)) {
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

        return ["{$relatedModelName}.{$relatedAttribute}" => $relatedModel->$relatedAttribute ?? null];
    }
}
