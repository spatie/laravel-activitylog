<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
                if (method_exists(Model::class, 'getRawOriginal')) {
                    // Laravel >7.0
                    $oldValues = (new static)->setRawAttributes($model->getRawOriginal());
                } else {
                    // Laravel <7.0
                    $oldValues = (new static)->setRawAttributes($model->getOriginal());
                }

                $model->oldAttributes = static::logChanges($oldValues);
            });
        }
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
                    $changes[$attribute] = $model->asDateTime($changes[$attribute])->format(explode(':',
                        $cast, 2)[1])
                    ;
                }
            }
        }

        return $changes;
    }

    protected static function getRelatedModelAttributeValue(Model $model, string $attribute): array
    {
        if (substr_count($attribute, '.') > 1) {
            throw CouldNotLogChanges::invalidAttribute($attribute);
        }

        [$relatedModelName, $relatedAttribute] = explode('.', $attribute);

        $relatedModelName = self::snake($relatedModelName);

        if ($relatedModel = $model->$relatedModelName) {
            return ["{$relatedModelName}.{$relatedAttribute}" => $relatedModel->$relatedAttribute ?? null];
        }
        $relatedModelName = self::camel($relatedModelName);

        if ($relatedModel = $model->$relatedModelName ?? $model->$relatedModelName()) {

            return ["{$relatedModelName}.{$relatedAttribute}" => $relatedModel->$relatedAttribute ?? null];
        }
    }

    protected static function getModelAttributeJsonValue(Model $model, string $attribute)
    {
        $path = explode('->', $attribute);
        $modelAttribute = array_shift($path);
        $modelAttribute = collect($model->getAttribute($modelAttribute));

        return data_get($modelAttribute, implode('.', $path));
    }
}
