<?php

namespace Luminous\Bridge;

use ArrayAccess;
use InvalidArgumentException;

abstract class Entity implements HasParameter, ArrayAccess
{
    use DecoratorTrait;
    use HasParameterTrait;

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
     * @param object $original
     * @param \Luminous\Bridge\Type $type
     * @return void
     */
    public function __construct($original, Type $type)
    {
        $this->original = $original;
        $this->accessorsForOriginal = $this->accessors;
        $this->type = $type;
    }
}
