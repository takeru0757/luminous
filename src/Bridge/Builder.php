<?php

namespace Luminous\Bridge;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Luminous\Bridge\Exceptions\EntityNotFoundException;
use Luminous\Bridge\Exceptions\EntityTypeNotExistException;

abstract class Builder
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Cached type instances.
     *
     * @var \Luminous\Bridge\Type[]
     */
    protected $types = [];

    /**
     * Create a new builder instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get an entity instance.
     *
     * @param mixed $id
     * @return \Luminous\Bridge\Entity
     *
     * @throws \Luminous\Bridge\Exceptions\EntityNotFoundException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function get($id)
    {
        if (! $original = call_user_func_array([$this, 'getOriginal'], func_get_args())) {
            throw new EntityNotFoundException;
        }

        return $this->make($original);
    }

    /**
     * Get an original object.
     *
     * @param mixed $id
     * @return object|null
     */
    abstract protected function getOriginal($id);

    /**
     * Hydrate an original object.
     *
     * @param object $original
     * @return \Luminous\Bridge\Entity
     */
    abstract public function make($original);

    /**
     * Hydrate many original objects.
     *
     * @param object[] $originals
     * @return \Illuminate\Support\Collection|\Luminous\Bridge\Entity[]
     */
    public function makeMany($originals)
    {
        return new Collection(array_map([$this, 'make'], $originals));
    }

    /**
     * Get a type instance.
     *
     * @param string $name
     * @return \Luminous\Bridge\Type
     *
     * @throws \Luminous\Bridge\Exceptions\EntityTypeNotExistException
     */
    public function getType($name)
    {
        if (array_key_exists($name, $this->types)) {
            return $this->types[$name];
        }

        if (! $original = $this->getOriginalType($name)) {
            throw new EntityTypeNotExistException;
        }

        return $this->types[$name] = $this->makeType($original);
    }

    /**
     * Get an original type object.
     *
     * @param string $name
     * @return object|null
     */
    abstract protected function getOriginalType($name);

    /**
     * Hydrate an original type object.
     *
     * @param object $original
     * @return \Luminous\Bridge\Type
     */
    abstract protected function makeType($original);
}
