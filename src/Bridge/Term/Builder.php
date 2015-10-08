<?php

namespace Luminous\Bridge\Term;

use InvalidArgumentException;
use Luminous\Bridge\Exceptions\MissingEntityException;
use Luminous\Bridge\Builder as BaseBuilder;

/**
 * @method \Luminous\Bridge\Term\Entity get(int|string|\stdClass $id, string $type = null)
 *         Get an entity instance.
 * @method \Luminous\Bridge\Term\Type getType(string $name) Get a type instance.
 */
class Builder extends BaseBuilder
{
    /**
     * Get an original object.
     *
     * @uses \get_term()
     * @uses \get_term_by()
     * @uses \is_wp_error()
     *
     * @param int|string|\stdClass $id
     * @param string $type
     * @return \stdClass|null
     *
     * @throws \InvalidArgumentException
     */
    protected function getOriginal($id, $type = null)
    {
        if (is_null($type) && isset($id->taxonomy)) {
            $type = $id->taxonomy;
        }

        if (empty($type)) {
            throw new InvalidArgumentException("Type must be specified.");
        }

        if (is_string($id)) {
            $paths = explode('/', $id);
            $original = get_term_by('slug', last($paths), $type);
        } else {
            $original = get_term($id, $type);
        }

        return $original && ! is_wp_error($original) ? $original : null;
    }

    /**
     * Hydrate an original object.
     *
     * @param \stdClass $original
     * @return \Luminous\Bridge\Term\Entity
     *
     * @throws \Luminous\Bridge\Exceptions\MissingEntityException
     */
    public function make($original)
    {
        $type = $this->getType($original->taxonomy);
        $base = 'wp.term.entities.';

        if (! $this->container->bound($abstract = $base.$type->name)) {
            $abstract = $base.($type->hierarchical ? 'hierarchical' : 'nonhierarchical');
            if (! $this->container->bound($abstract)) {
                throw new MissingEntityException($abstract);
            }
        }

        return $this->container->make($abstract, [$this->container['wp'], $original, $type]);
    }

    /**
     * Get an original type object.
     *
     * @uses \get_taxonomy()
     *
     * @param string $name
     * @return \stdClass|null
     */
    protected function getOriginalType($name)
    {
        return get_taxonomy($name) ?: null;
    }

    /**
     * Hydrate an original type object.
     *
     * @param \stdClass $original
     * @return \Luminous\Bridge\Term\Type
     */
    protected function makeType($original)
    {
        return new Type($this->container['wp'], $original);
    }
}
