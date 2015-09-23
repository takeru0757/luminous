<?php

namespace Luminous\Bridge\Term;

use stdClass;
use Luminous\Bridge\Exceptions\MissingEntityException;
use Luminous\Bridge\Exceptions\RecordNotFoundException;
use Luminous\Bridge\Term\Entities\CategoryEntity;
use Luminous\Bridge\Term\Entities\PostTagEntity;

class Builder
{
    /**
     * The map of entity classes.
     *
     * @var string[string]
     */
    protected static $entityClasses = [
        'category' => CategoryEntity::class,
        'post_tag' => PostTagEntity::class,
    ];

    /**
     * Find the entity.
     *
     * @uses \get_term()
     * @uses \is_wp_error()
     * @param int|\stdClass $id
     * @param string $type
     * @return \Luminous\Bridge\Term\Entities\Entity
     *
     * @throws \Luminous\Bridge\Exceptions\RecordNotFoundException
     */
    public static function get($id, $type = null)
    {
        if (is_object($id) && isset($id->taxonomy)) {
            return static::hydrate($id);
        } elseif (($term = get_term($id, $type)) && !is_wp_error($term)) {
            return static::hydrate($term);
        }

        throw new RecordNotFoundException;
    }

    /**
     * Get the entity class name.
     *
     * @param string $type
     * @return string
     *
     * @throws \Luminous\Bridge\Exceptions\MissingEntityException
     */
    public static function entityClass($type = null)
    {
        if (! isset(static::$entityClasses[$type])) {
            throw new MissingEntityException;
        }

        return static::$entityClasses[$type];
    }

    /**
     * Create an entity.
     *
     * @param \stdClass $original
     * @return \Luminous\Bridge\Term\Entities\Entity
     */
    public static function hydrate(stdClass $original)
    {
        $entityClass = static::entityClass($original->taxonomy);
        return new $entityClass($original);
    }

    /**
     * Create a collection of entities.
     *
     * @param array $originals
     * @return array
     */
    public static function hydrateMany(array $originals)
    {
        return array_map(get_called_class().'::hydrate', $originals);
    }
}
