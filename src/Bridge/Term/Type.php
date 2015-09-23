<?php

namespace Luminous\Bridge\Term;

use InvalidArgumentException;
use Luminous\Bridge\EntityAttributeTrait;

class Type
{
    use EntityAttributeTrait;

    /**
     * Instances.
     *
     * @var \Luminous\Bridge\Term\Type[]
     */
    protected static $instances = [];

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [];

    /**
     * Get the term type (taxonomy) instance.
     *
     * @param string $name
     * @return \Luminous\Bridge\Term\Type[]
     */
    public static function get($name)
    {
        if (! array_key_exists($name, static::$instances)) {
            static::$instances[$name] = new static($name);
        }

        return static::$instances[$name];
    }

    /**
     * Create new term type (taxonomy) instance.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     */
    protected function __construct($name)
    {
        $this->original = get_taxonomy($name);

        if (! $this->original) {
            throw new InvalidArgumentException("No term type named [{$name}].");
        }
    }
}
