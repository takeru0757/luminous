<?php

namespace Luminous\Bridge;

use ArrayAccess;
use InvalidArgumentException;

abstract class Entity implements ArrayAccess
{
    use DecoratorTrait;

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
     * The type.
     *
     * @var \Luminous\Bridge\Type
     */
    public $type;

    /**
     * Create a new entity instance.
     *
     * @param \Luminous\Bridge\WP $wp
     * @param object $original
     * @param \Luminous\Bridge\Type $type
     * @return void
     */
    public function __construct(WP $wp, $original, Type $type)
    {
        $this->wp = $wp;
        $this->original = $original;
        $this->accessorsForOriginal = $this->accessors;
        $this->type = $type;
    }

    /**
     * Get a URL parameter.
     *
     * @param string $key
     * @return string
     */
    public function urlParameter($key)
    {
        return $this->{$key};
    }
}
