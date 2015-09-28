<?php

namespace Luminous\Bridge;

use ArrayAccess;
use InvalidArgumentException;

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
     * Create new type instance.
     *
     * @param mixed $original
     * @return void
     */
    public function __construct($original)
    {
        $this->original = $original;
        $this->accessorsForOriginal = $this->accessors;
    }
}
