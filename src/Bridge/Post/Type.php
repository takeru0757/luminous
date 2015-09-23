<?php

namespace Luminous\Bridge\Post;

use InvalidArgumentException;
use Luminous\Bridge\EntityAttributeTrait;

class Type
{
    use EntityAttributeTrait;

    /**
     * Instances.
     *
     * @var \Luminous\Bridge\Post\Type[]
     */
    protected static $instances = [];

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [];

    /**
     * Get the post type instance.
     *
     * @param string $name
     * @return \Luminous\Bridge\Post\Type[]
     */
    public static function get($name)
    {
        if (! array_key_exists($name, static::$instances)) {
            static::$instances[$name] = new static($name);
        }

        return static::$instances[$name];
    }

    /**
     * Create new post type instance.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     */
    protected function __construct($name)
    {
        $this->original = get_post_type_object($name);

        if (! $this->original) {
            throw new InvalidArgumentException("No post type named [{$name}].");
        }
    }
}
