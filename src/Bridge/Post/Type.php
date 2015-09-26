<?php

namespace Luminous\Bridge\Post;

use Luminous\Bridge\EntityAttributeTrait;
use Luminous\Bridge\HasArchive;

class Type implements HasArchive
{
    use EntityAttributeTrait;

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [];

    /**
     * Create new post type instance.
     *
     * @param \stdClass $original
     * @return void
     */
    public function __construct($original)
    {
        $this->original = $original;
    }

    /**
     * Get the route prefix for archive of this instance.
     *
     * @return string
     */
    public function getRoutePrefix()
    {
        return $this->name;
    }

    /**
     * Wheter this instance has archive.
     *
     * @return bool
     */
    public function hasArchive()
    {
        return $this->original->public && ! $this->original->hierarchical;
    }
}
