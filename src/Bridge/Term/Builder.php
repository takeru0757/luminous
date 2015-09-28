<?php

namespace Luminous\Bridge\Term;

use Luminous\Bridge\Exceptions\MissingEntityException;
use Luminous\Bridge\Exceptions\RecordNotFoundException;
use Luminous\Bridge\Exceptions\TypeNotExistException;
use Luminous\Bridge\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * Get an entity instance.
     *
     * @param mixed $id
     * @param string $type
     * @return \Luminous\Bridge\Entity
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function get($id, $type = null)
    {
        return parent::get($id, $type);
    }

    /**
     * Get an original object.
     *
     * @uses \get_term()
     * @uses \is_wp_error()
     *
     * @param int|\stdClass $id
     * @param string $type
     * @return \stdClass
     *
     * @throws \Luminous\Bridge\Exceptions\RecordNotFoundException
     */
    protected function getOriginal($id, $type = null)
    {
        if (is_null($type) && isset($id->taxonomy)) {
            $type = $id->taxonomy;
        }

        if (($original = get_term($id, $type)) && ! is_wp_error($original)) {
            return $original;
        }

        throw new RecordNotFoundException([is_object($id) ? $id->term_id : $id, "terms ({$type})"]);
    }

    /**
     * Hydrate an original object.
     *
     * @param \stdClass $original
     * @return \Luminous\Bridge\Term\Entities\Entity
     *
     * @throws \Luminous\Bridge\Exceptions\MissingEntityException
     */
    public function make($original)
    {
        $type = $this->getType($original->taxonomy);
        $base = 'wp.term.entities.';

        if (! $this->container->bound($abstract = "{$base}{$type->name}")) {
            if (! $this->container->bound($abstract = $base.($type->hierarchical ? 'category' : 'post_tag'))) {
                throw new MissingEntityException([$abstract]);
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
     * @return \stdClass
     *
     * @throws \Luminous\Bridge\Exceptions\TypeNotExistException
     */
    protected function getOriginalType($name)
    {
        if ($original = get_taxonomy($name)) {
            return $original;
        }

        throw new TypeNotExistException([$name, 'terms']);
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
}
