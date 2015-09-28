<?php

namespace Luminous\Bridge;

use stdClass;
use ArrayAccess;

abstract class Type implements ArrayAccess
{
    use DecoratorTrait;

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
}
