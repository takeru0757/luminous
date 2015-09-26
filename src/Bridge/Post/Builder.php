<?php

namespace Luminous\Bridge\Post;

use Luminous\Bridge\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * Get the original object.
     *
     * @uses \get_post()
     *
     * @param mixed $id
     * @param string $type
     * @return \WP_Post|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getOriginal($id, $type = null)
    {
        return get_post($id);
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
     * @uses \get_post_type_object()
     *
     * @param string $name
     * @return \stdClass
     */
    protected function getOriginalType($name)
    {
        return get_post_type_object($name);
    }

    /**
     * Get the entity type from original object.
     *
     * @param mixed $original
     * @return string
     */
    protected function entityType($original)
    {
        return $this->getType($original->post_type);
    }

    /**
     * Get the entity abstract from the type.
     *
     * @param \Luminous\Bridge\Type $type
     * @return string
     */
    protected function entityAbstract($type)
    {
        $base = 'wp.post';

        if ($this->container->bound("{$base}.{$type->name}")) {
            $key = $type->name;
        } else {
            $key = $type->hierarchical ? 'page' : 'post';
        }

        return "{$base}.{$key}";
    }

    /**
     * Get a new query instance.
     *
     * @return \Luminous\Bridge\Post\Query
     */
    public function query()
    {
        return new Query($this);
    }
}
