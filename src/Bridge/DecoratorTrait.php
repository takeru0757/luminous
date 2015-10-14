<?php

namespace Luminous\Bridge;

use Exception;
use Illuminate\Support\Str;

trait DecoratorTrait
{
    /**
     * The original instance.
     *
     * @var object
     */
    protected $original;

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessorsForOriginal = [];

    /**
     * The cached attributes.
     *
     * @var array
     */
    protected $cachedAttributes = [];

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
        if (! array_key_exists($key, $this->cachedAttributes)) {
            $value = $this->getOriginalAttribute($key);

            if ($mutator = $this->getMutator($key)) {
                $value = $this->{$mutator}($value);
            }

            $this->cachedAttributes[$key] = $value;
        }

        return $this->cachedAttributes[$key];
    }

    /**
     * Get an attribute from the original object.
     *
     * @param string $key
     * @return mixed
     */
    public function getOriginalAttribute($key)
    {
        return ($accessor = $this->accessor($key)) ? $this->original->{$accessor} : null;
    }

    /**
     * Get the accessor to get an attribute from original obejct.
     *
     * @param string $key
     * @return string
     */
    protected function accessor($key)
    {
        if (isset($this->accessorsForOriginal[$key])) {
            return $this->accessorsForOriginal[$key];
        }

        return property_exists($this->original, $key) ? $key : null;
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

    /**
     * ArrayAccess::offsetExists()
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->accessor($key) || $this->getMutator($key);
    }

    /**
     * ArrayAccess::offsetGet()
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * ArrayAccess::offsetSet()
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function offsetSet($key, $value)
    {
        throw new Exception(__FUNCTION__.' is not implemented.');
    }

    /**
     * ArrayAccess::offsetUnset()
     *
     * @param mixed $key
     * @return void
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function offsetUnset($key)
    {
        throw new Exception(__FUNCTION__.' is not implemented.');
    }
}
