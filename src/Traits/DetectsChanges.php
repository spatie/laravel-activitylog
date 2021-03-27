<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\ActivitylogOptions;

trait DetectsChanges
{
    protected array $oldAttributes = [];

    protected ActivitylogOptions $activitylogOptions;

    abstract public function getActivitylogOptions(): ActivitylogOptions;

    protected static function bootDetectsChanges(): void
    {
        if (static::eventsToBeRecorded()->contains('updated')) {
            static::updating(function (Model $model) {
                $model->activitylogOptions = $model->getActivitylogOptions();

                //temporary hold the original attributes on the model
                //as we'll need these in the updating event
                $oldValues = (new static)->setRawAttributes($model->getRawOriginal());

                $model->oldAttributes = static::logChanges($oldValues);
            });
        }
    }

    public function attributesToBeLogged(): array
    {
        $this->activitylogOptions = $this->getActivitylogOptions();

        $attributes = [];

        if ($this->activitylogOptions->logFillable) {
            $attributes = array_merge($attributes, $this->getFillable());
        }

        if ($this->shouldLogUnguarded()) {
            $attributes = array_merge($attributes, array_diff(array_keys($this->getAttributes()), $this->getGuarded()));
        }

        if (! empty($this->activitylogOptions->logAttributes)) {
            $attributes = array_merge($attributes, array_diff($this->activitylogOptions->logAttributes, ['*']));

            if (in_array('*', $this->activitylogOptions->logAttributes)) {
                $attributes = array_merge($attributes, array_keys($this->getAttributes()));
            }
        }

        if ($this->activitylogOptions->ignoredAttributes) {
            $attributes = array_diff($attributes, $this->activitylogOptions->ignoredAttributes);
        }

        return $attributes;
    }


    public function shouldLogUnguarded(): bool
    {
        if (! $this->activitylogOptions->logUnguarded) {
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
            $processingEvent == 'retrieved'
                ? $this
                : (
                    $this->exists
                        ? $this->fresh() ?? $this
                        : $this
                )
        );

        if (static::eventsToBeRecorded()->contains('updated') && $processingEvent == 'updated') {
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);

            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);

            $this->oldAttributes = [];
        }

        if ($this->activitylogOptions->logOnlyDirty && isset($properties['old'])) {
            $properties['attributes'] = array_udiff_assoc(
                $properties['attributes'],
                $properties['old'],
                function ($new, $old) {
                    if ($old === null || $new === null) {
                        return $new === $old ? 0 : 1;
                    }

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

        foreach ($attributes as $attribute) {
            if (Str::contains($attribute, '.')) {
                $changes += self::getRelatedModelAttributeValue($model, $attribute);

                continue;
            }

            if (Str::contains($attribute, '->')) {
                Arr::set(
                    $changes,
                    str_replace('->', '.', $attribute),
                    static::getModelAttributeJsonValue($model, $attribute)
                );

                continue;
            }

            $changes[$attribute] = $model->getAttribute($attribute);

            if (is_null($changes[$attribute])) {
                continue;
            }

            if ($model->isDateAttribute($attribute)) {
                $changes[$attribute] = $model->serializeDate(
                    $model->asDateTime($changes[$attribute])
                );
            }

            if ($model->hasCast($attribute)) {
                $cast = $model->getCasts()[$attribute];

                if ($model->isCustomDateTimeCast($cast)) {
                    $changes[$attribute] = $model->asDateTime($changes[$attribute])->format(explode(':', $cast, 2)[1]);
                }
            }
        }

        return $changes;
    }

    protected static function getRelatedModelAttributeValue(Model $model, string $attribute): array
    {
        $relatedModelNames = explode('.', $attribute);
        $relatedAttribute = array_pop($relatedModelNames);

        $attributeName = [];
        $relatedModel = $model;

        do {
            $attributeName[] = $relatedModelName = static::getRelatedModelRelationName($relatedModel, array_shift($relatedModelNames));

            $relatedModel = $relatedModel->$relatedModelName ?? $relatedModel->$relatedModelName();
        } while (! empty($relatedModelNames));

        $attributeName[] = $relatedAttribute;

        return [implode('.', $attributeName) => $relatedModel->$relatedAttribute ?? null];
    }

    protected static function getRelatedModelRelationName(Model $model, string $relation): string
    {
        return Arr::first([
            $relation,
            Str::snake($relation),
            Str::camel($relation),
        ], function (string $method) use ($model): bool {
            return method_exists($model, $method);
        }, $relation);
    }

    protected static function getModelAttributeJsonValue(Model $model, string $attribute): mixed
    {
        $path = explode('->', $attribute);
        $modelAttribute = array_shift($path);
        $modelAttribute = collect($model->getAttribute($modelAttribute));

        return data_get($modelAttribute, implode('.', $path));
    }
}
