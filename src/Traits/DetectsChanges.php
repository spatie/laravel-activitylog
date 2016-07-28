<?php

namespace Spatie\Activitylog\Traits;

use Illuminate\Database\Eloquent\Model;

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


    /**
     * @return array
     */
    public function attributesToBeLogged()
    {
        if (!isset(static::$logAttributes)) {
            return [];
        }

        return static::$logAttributes;
    }


    /**
     * @param string $processingEvent
     *
     * @return array
     */
    public function attributeValuesToBeLogged($processingEvent)
    {
        if (!count($this->attributesToBeLogged())) {
            return [];
        }

        $properties['attributes'] = static::logChanges($this);

        if (static::eventsToBeRecorded()->contains('updated') && $processingEvent == 'updated') {
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);

            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);
        }

        return $properties;
    }


    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return array
     */
    public static function logChanges(Model $model)
    {
        return collect($model)->only($model->attributesToBeLogged())->toArray();
    }
}
