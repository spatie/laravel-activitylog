<?php

namespace Spatie\Activitylog\Support;

use Carbon\CarbonInterval;
use DateInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChangeDetector
{
    public static function resolveRelatedAttribute(Model $model, string $attribute): array
    {
        $segments = explode('.', $attribute);
        $relatedAttribute = array_pop($segments);

        $path = [];
        $relatedModel = $model;

        foreach ($segments as $segment) {
            $relationName = static::resolveRelationName($relatedModel, $segment);
            $path[] = $relationName;
            $relatedModel = $relatedModel->$relationName ?? $relatedModel->$relationName();

            if (is_null($relatedModel)) {
                $path[] = $relatedAttribute;

                return [implode('.', $path) => null];
            }
        }

        $path[] = $relatedAttribute;

        return [implode('.', $path) => $relatedModel->$relatedAttribute ?? null];
    }

    public static function resolveRelationName(Model $model, string $relation): string
    {
        return array_find(
            [$relation, Str::snake($relation), Str::camel($relation)],
            fn (string $method): bool => method_exists($model, $method)
        ) ?? $relation;
    }

    public static function resolveJsonAttribute(Model $model, string $attribute): mixed
    {
        $path = explode('->', $attribute);
        $modelAttribute = array_shift($path);

        return data_get(
            collect($model->getAttribute($modelAttribute)),
            implode('.', $path)
        );
    }

    public static function filterDirty(array $newAttributes, array $oldAttributes): array
    {
        $changed = array_udiff_assoc(
            $newAttributes,
            $oldAttributes,
            fn ($new, $old) => static::compareValues($new, $old)
        );

        $old = collect($oldAttributes)
            ->only(array_keys($changed))
            ->all();

        return ['attributes' => $changed, 'old' => $old];
    }

    protected static function compareValues(mixed $new, mixed $old): int
    {
        if ($old === null) {
            return $new === null ? 0 : 1;
        }

        if ($new === null) {
            return 1;
        }

        if ($old instanceof DateInterval) {
            return CarbonInterval::make($old)->equalTo($new) ? 0 : 1;
        }

        if ($new instanceof DateInterval) {
            return CarbonInterval::make($new)->equalTo($old) ? 0 : 1;
        }

        return $new <=> $old;
    }
}
