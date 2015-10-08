<?php

namespace Luminous\Bridge;

use stdClass;
use ArrayAccess;

abstract class Type implements ArrayAccess
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
}
