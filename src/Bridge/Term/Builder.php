<?php

namespace Luminous\Bridge\Term;

use Luminous\Bridge\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * Get the original object.
     *
     * @uses \get_term()
     * @uses \is_wp_error()
     *
     * @param mixed $id
     * @param string $type
     * @return \stdClass|null
     */
    protected function getOriginal($id, $type = null)
    {
        $original = get_term($id, $type);
        return $original && ! is_wp_error($original) ? $original : null;
    }

    /**
     * Get the type class name.
     *
     * @return string
     */
    protected function typeClass()
    {
        return Type::class;
    }

    /**
     * Get the original type object.
     *
     * @uses \get_taxonomy()
     *
     * @param string $name
     * @return \stdClass
     */
    protected function getOriginalType($name)
    {
        return get_taxonomy($name);
    }

    /**
     * Get the entity type from original object.
     *
     * @param mixed $original
     * @return string
     */
    protected function entityType($original)
    {
        return $this->getType($original->taxonomy);
    }

    /**
     * Get the entity abstract from the type.
     *
     * @param \Luminous\Bridge\Type $type
     * @return string
     */
    protected function entityAbstract($type)
    {
        $base = 'wp.term';

        if ($this->container->bound("{$base}.{$type->name}")) {
            $key = $type->name;
        } else {
            $key = $type->hierarchical ? 'category' : 'post_tag';
        }

        return "{$base}.{$key}";
    }
}
