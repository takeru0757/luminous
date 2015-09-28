<?php

namespace Luminous\Bridge\Post;

use Luminous\Bridge\HasArchive;
use Luminous\Bridge\Type as BaseType;

class Type extends BaseType implements HasArchive
{
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
