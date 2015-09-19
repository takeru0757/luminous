<?php

namespace Luminous\Bridge;

use Illuminate\Support\Str;

trait EntityAttributeTrait
{
    /**
     * The original instance.
     *
     * @var mixed
     */
    protected $original;

    /**
     * Dynamically retrieve attributes on the entity.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Get an attribute from the entity.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = $this->getOriginalAttribute($key);

        if ($mutator = $this->getMutator($key)) {
            return $this->{$mutator}($value);
        }

        return $value;
    }

    /**
     * Get an attribute from the original object.
     *
     * @param string $key
     * @return mixed
     */
    public function getOriginalAttribute($key)
    {
        return $this->original->{$this->accessor($key)};
    }

    /**
     * Get the accessor to get an attribute from original obejct.
     *
     * @param string $key
     * @return string
     */
    protected function accessor($key)
    {
        return isset($this->accessors[$key]) ? $this->accessors[$key] : $key;
    }

    /**
     * Get the mutator method name to get an attribute value.
     *
     * @param string $key
     * @return string
     */
    protected function getMutator($key)
    {
        $method = 'get'.Str::studly($key).'Attribute';
        return method_exists($this, $method) ? $method : null;
    }
}
