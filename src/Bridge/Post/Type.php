<?php

namespace Luminous\Bridge\Post;

use Luminous\Bridge\Type as BaseType;

class Type extends BaseType
{
    /**
     * Get the type of type.
     *
     * @return string
     */
    public function type()
    {
        return 'post';
    }

    /**
     * Determine if this type has archive.
     *
     * @return bool
     */
    public function hasArchive()
    {
        return ! $this->original->hierarchical;
    }
}
