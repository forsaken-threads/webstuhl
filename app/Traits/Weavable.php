<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

/**
 * Class Weavable
 * @package App\Resources
 *
 * @method static creating(callable $callback)
 */
trait Weavable
{
    abstract public function getValidationRules();

    /**
     *
     */
    public static function bootWeavable()
    {
        // Webstuhl resources use UUIDs for primary keys
        static::creating(function ($model) {
            /** @var Model $model */
            $model->{$model->getKeyName()} = (string) Uuid::uuid4();
        });
    }
}