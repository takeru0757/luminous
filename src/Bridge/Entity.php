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
}
