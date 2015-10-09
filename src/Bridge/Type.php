<?php

namespace Luminous\Bridge;

use stdClass;
use ArrayAccess;

abstract class Type implements ArrayAccess, UrlResource
{
    use DecoratorTrait;
    use UrlPathTrait;

    /**
     * The WP.
     *
     * @var \Luminous\Bridge\WP
     */
    protected $wp;

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [];

    /**
     * Create a new type instance.
     *
     * @param \Luminous\Bridge\WP $wp
     * @param \stdClass $original
     * @return void
     */
    public function __construct(WP $wp, stdClass $original)
    {
        $this->wp = $wp;
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
