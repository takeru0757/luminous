<?php

namespace Luminous\Bridge\Post;

use WP_Post;
use Luminous\Bridge\Exceptions\MissingEntityException;
use Luminous\Bridge\Exceptions\RecordNotFoundException;
use Luminous\Bridge\Post\Query;
use Luminous\Bridge\Post\Entities\AttachmentEntity;
use Luminous\Bridge\Post\Entities\PageEntity;
use Luminous\Bridge\Post\Entities\PostEntity;

class Builder
{
    /**
     * The map of entity classes.
     *
     * @var string[string]
     */
    protected static $entityClasses = [
        'attachment' => AttachmentEntity::class,
        'page' => PageEntity::class,
        'post' => PostEntity::class,
    ];

    /**
     * Get a new query builder instance.
     *
     * @return \Luminous\Bridge\Post\Query
     */
    public static function query()
    {
        return new Query(new static);
    }

    /**
     * Find the entity.
     *
     * @uses \get_post()
     * @param int|\WP_Post $id
     * @return \Luminous\Bridge\Post\Entities\Entity
     *
     * @throws \Luminous\Bridge\Exceptions\RecordNotFoundException
     */
    public static function get($id)
    {
        if ($post = get_post($id)) {
            return static::hydrate($post);
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
     * @param \WP_Post $original
     * @return \Luminous\Bridge\Post\Entities\Entity
     */
    public static function hydrate(WP_Post $original)
    {
        $entityClass = static::entityClass($original->post_type);
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

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $query = static::query();
        return call_user_func_array([$query, $method], $parameters);
    }
}
