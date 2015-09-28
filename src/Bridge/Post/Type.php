<?php

namespace Luminous\Bridge\Post;

use Luminous\Bridge\HasArchive;
use Luminous\Bridge\Type as BaseType;

class Type extends BaseType implements HasArchive
{
    /**
     * Get the route type for this instance.
     *
     * @return string
     */
    public function getRouteType()
    {
        return $this->name;
    }

    /**
     * Determine if this instance allows to show archive.
     *
     * @return bool
     */
    public function allowArchive()
    {
        return $this->original->public && ! $this->original->hierarchical;
    }
}
