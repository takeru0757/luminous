<?php

namespace Luminous\Bridge\Term;

use InvalidArgumentException;
use Luminous\Bridge\Builder as BaseBuilder;
use Luminous\Bridge\WP;
use Luminous\Bridge\Exceptions\MissingEntityException;
use Luminous\Bridge\Term\Query\Builder as QueryBuilder;

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
     * @param \Luminous\Bridge\Term\Type|string $type
     * @return \stdClass|null
     *
     * @throws \InvalidArgumentException
     */
    protected function getOriginal($id, $type = null)
    {
        if (is_null($type) && isset($id->taxonomy)) {
            $type = $id->taxonomy;
        } elseif ($type instanceof Type) {
            $type = $type->name;
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

        if (! $this->container->bound($abstract = "wp.term.entities.{$type->name}")) {
            $abstract = $type->hierarchical
                ? 'Luminous\Bridge\Term\Entities\HierarchicalEntity'
                : 'Luminous\Bridge\Term\Entities\NonHierarchicalEntity';

            if (! $this->container->bound($abstract)) {
                throw new MissingEntityException($abstract);
            }
        }

        return $this->container->make($abstract, [$type, $original]);
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
        return new Type($original);
    }

    /**
     * Create a new query instance.
     *
     * @return \Luminous\Bridge\Term\Query\Builder
     */
    public function query()
    {
        return new QueryBuilder($this);
    }
}
