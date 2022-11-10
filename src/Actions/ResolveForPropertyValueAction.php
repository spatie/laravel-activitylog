<?php

namespace Spatie\Activitylog\Actions;

class ResolveForPropertyValueAction
{
    /**
     * Action that resolve property value of log
     * that cannot be handled by PHP as default
     *
     * @param mixed $value
     * @return mixed
     */
    public static function execute(mixed $value): mixed
    {
        $instance = new static;

        /**
         * Give a fallback value if value not a backed enum
         */
        if ($instance->isValueAnEnum($value)) {
            return $value->value ?? $value->name;
        }

        return $value;
    }

    protected function isValueAnEnum($value): bool
    {
        if (! function_exists('enum_exists')){
            return false;
        }

        $enumNamespace = is_object($value) ? get_class($value): $value;

        return ! is_array($value) && enum_exists($enumNamespace);
    }
}
