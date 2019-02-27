<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Support\Str;
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
            $attributes = array_merge($attributes, $this->getFillable());
        }

        if ($this->shouldLogUnguarded()) {
            $attributes = array_merge($attributes, array_diff(array_keys($this->getAttributes()), $this->getGuarded()));
        }

        if (isset(static::$logAttributes) && is_array(static::$logAttributes)) {
            $attributes = array_merge($attributes, array_diff(static::$logAttributes, ['*']));

            if (in_array('*', static::$logAttributes)) {
                $attributes = array_merge($attributes, array_keys($this->getAttributes()));
            }
        }

        if (isset(static::$logAttributesToIgnore) && is_array(static::$logAttributesToIgnore)) {
            $attributes = array_diff($attributes, static::$logAttributesToIgnore);
        }

        return $attributes;
    }

    public function shouldLogOnlyDirty(): bool
    {
        if (! isset(static::$logOnlyDirty)) {
            return false;
        }

        return static::$logOnlyDirty;
    }

    public function shouldLogUnguarded(): bool
    {
        if (! isset(static::$logUnguarded)) {
            return false;
        }

        if (! static::$logUnguarded) {
            return false;
        }

        if (in_array('*', $this->getGuarded())) {
            return false;
        }

        return true;
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

        if ($this->shouldLogOnlyDirty() && isset($properties['old'])) {
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
        $attributes = $model->attributesToBeLogged();
        $model = clone $model;
        $model->append(array_filter($attributes, function ($key) use ($model) {
            return $model->hasGetMutator($key);
        }));
        $model->setHidden(array_diff($model->getHidden(), $attributes));
        $collection = collect($model);

        foreach ($attributes as $attribute) {
            if (Str::contains($attribute, '.')) {
                $changes += self::getRelatedModelAttributeValue($model, $attribute);
            } else {
                $changes += $collection->only($attribute)->toArray();
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
