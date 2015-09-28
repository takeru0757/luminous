<?php

namespace Luminous\Bridge\Post;

use Luminous\Bridge\Exceptions\MissingEntityException;
use Luminous\Bridge\Builder as BaseBuilder;

/**
 * @method \Luminous\Bridge\Post\Entities\Entity get(int|\WP_Post $id) Get an entity instance.
 * @method \Luminous\Bridge\Post\Type getType(string $name) Get a type instance.
 */
class Builder extends BaseBuilder
{
    /**
     * Get an original object.
     *
     * @uses \get_post()
     *
     * @param int|\WP_Post $id
     * @return \WP_Post|null
     */
    protected function getOriginal($id)
    {
        // WordPress uses global $post when $id is null.
        return $id && ($original = get_post($id)) ? $original : null;
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
                throw new MissingEntityException($abstract);
            }
        }

        return $this->container->make($abstract, [$original, $type]);
    }

    /**
     * Get an original type object.
     *
     * @uses \get_post_type_object()
     *
     * @param string $name
     * @return \stdClass|null
     */
    protected function getOriginalType($name)
    {
        return get_post_type_object($name) ?: null;
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
