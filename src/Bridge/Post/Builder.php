<?php

namespace Luminous\Bridge\Post;

use Luminous\Bridge\Exceptions\MissingEntityException;
use Luminous\Bridge\Exceptions\RecordNotFoundException;
use Luminous\Bridge\Exceptions\TypeNotExistException;
use Luminous\Bridge\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * Get an original object.
     *
     * @uses \get_post()
     *
     * @param int|\WP_Post $id
     * @return \WP_Post
     *
     * @throws \Luminous\Bridge\Exceptions\RecordNotFoundException
     */
    protected function getOriginal($id)
    {
        // WordPress uses global $post when $id is null.
        if ($id && $original = get_post($id)) {
            return $original;
        }

        throw new RecordNotFoundException([is_object($id) ? $id->ID : $id, 'posts']);
    }

    /**
     * Hydrate an original object.
     *
     * @param \WP_Post $original
     * @return \Luminous\Bridge\Post\Entities\Entity
     *
     * @throws \Luminous\Bridge\Exceptions\MissingEntityException
     */
    public function make($original)
    {
        $type = $this->getType($original->post_type);
        $base = 'wp.post.entities.';

        if (! $this->container->bound($abstract = "{$base}{$type->name}")) {
            if (! $this->container->bound($abstract = $base.($type->hierarchical ? 'page' : 'post'))) {
                throw new MissingEntityException([$abstract]);
            }
        }

        return $this->container->make($abstract, [$type, $original]);
    }

    /**
     * Get an original type object.
     *
     * @uses \get_post_type_object()
     *
     * @param string $name
     * @return \stdClass
     *
     * @throws \Luminous\Bridge\Exceptions\TypeNotExistException
     */
    protected function getOriginalType($name)
    {
        if ($original = get_post_type_object($name)) {
            return $original;
        }

        throw new TypeNotExistException([$name, 'posts']);
    }

    /**
     * Hydrate an original type object.
     *
     * @param \stdClass $original
     * @return \Luminous\Bridge\Post\Type
     */
    protected function makeType($original)
    {
        return new Type($original);
    }

    /**
     * Create a new query instance.
     *
     * @return \Luminous\Bridge\Post\Query
     */
    public function query()
    {
        return new Query($this);
    }
}
