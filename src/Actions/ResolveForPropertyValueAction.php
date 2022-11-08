<?php

namespace Spatie\Activitylog\Actions;

class ResolveForPropertyValueAction
{
    /**
     * Action that resolve property value of log
     * that cannot be handled by PHP or This Package
     *
     * @param mixed $value
     * @return mixed
     */
    public static function execute(mixed $value): mixed
    {
        $instance = new static;

        if ($instance->isValueAreEnum($value)) {
            return $value->value ?? $value->name;
        }

        return $value;
    }

    protected function isValueAreEnum($value): bool
    {
        if (! function_exists('enum_exists')){
            return false;
        }

        $enumNamespace = is_object($value)
            ? get_class($value)
            : $value;

        return enum_exists($enumNamespace);
    }
}
