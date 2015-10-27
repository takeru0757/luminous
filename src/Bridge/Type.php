<?php

namespace Luminous\Bridge;

use ArrayAccess;
use stdClass;

abstract class Type implements ArrayAccess, UrlResource
{
    use DecoratorTrait;
    use UrlPathTrait;

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [];

    /**
     * Create a new type instance.
     *
     * @param \stdClass $original
     * @return void
     */
    public function __construct(stdClass $original)
    {
        $this->original = $original;
        $this->accessorsForOriginal = $this->accessors;
    }

    /**
     * Get the type of type.
     *
     * @return string
     */
    abstract public function type();

    /**
     * Get the array to build URL.
     *
     * @return array
     */
    public function forUrl()
    {
        $type = $this->type();

        return ["{$type}_type" => $this->name];
    }
}
