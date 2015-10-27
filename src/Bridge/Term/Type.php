<?php

namespace Luminous\Bridge\Term;

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
        return 'term';
    }

    /**
     * Get the post type.
     *
     * @return \Luminous\Bridge\Post\Type
     */
    protected function getPostTypeAttribute()
    {
        $postType = $this->original->object_type[0];
        return WP::postType($postType);
    }
}
