<?php

namespace Luminous\Bridge;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Luminous\Bridge\Exceptions\MissingEntityException;
use Luminous\Bridge\Exceptions\RecordNotFoundException;
use Luminous\Bridge\Exceptions\TypeNotExistException;

abstract class Builder
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Type instances.
     *
     * @var \Luminous\Bridge\Type[]
     */
    protected $types = [];

    /**
     * Create a new post builder instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get the entity.
     *
     * @param mixed $id
     * @param string $type
     * @return \Luminous\Bridge\Entity
     *
     * @throws \Luminous\Bridge\Exceptions\RecordNotFoundException
     */
    public function get($id, $type = null)
    {
        if (! $original = $this->getOriginal($id, $type)) {
            throw new RecordNotFoundException("Record [$id] not found.");
        }

        return $this->hydrate($original);
    }

    /**
     * Get the original object.
     *
     * @param mixed $id
     * @param string $type
     * @return mixed|null
     */
    abstract protected function getOriginal($id, $type = null);

    /**
     * Get the type instance.
     *
     * @param string $name
     * @return \Luminous\Bridge\Type
     *
     * @throws \Luminous\Bridge\Exceptions\TypeNotExistException
     */
    public function getType($name)
    {
        if (array_key_exists($name, $this->types)) {
            return $this->types[$name];
        }

        if (! $original = $this->getOriginalType($name)) {
            throw new TypeNotExistException("Type [$name] does not exist.");
        }

        $class = $this->typeClass();
        return $this->types[$name] = new $class($original);
    }

    /**
     * Get the type class name.
     *
     * @return string
     */
    abstract protected function typeClass();

    /**
     * Get the original type object.
     *
     * @param string $name
     * @return mixed
     */
    abstract protected function getOriginalType($name);

    /**
     * Get the entity type from original object.
     *
     * @param mixed $original
     * @return string
     */
    abstract protected function entityType($original);

    /**
     * Get the entity abstract from the type.
     *
     * @param \Luminous\Bridge\Type $type
     * @return string
     */
    abstract protected function entityAbstract($type);

    /**
     * Create an entity from original object.
     *
     * @param mixed $original
     * @return \Luminous\Bridge\Entity
     *
     * @throws \Luminous\Bridge\Exceptions\MissingEntityException
     */
    public function hydrate($original)
    {
        $type = $this->entityType($original);
        $abstract = $this->entityAbstract($type);

        if (! $this->container->bound($abstract)) {
            throw new MissingEntityException("Entity class [$abstract] could not be found.");
        }

        return $this->container->make($abstract, [$original, $type]);
    }

    /**
     * Create a collection of entities from original objects.
     *
     * @param array $originals
     * @return \Illuminate\Support\Collection|\Luminous\Bridge\Entity[]
     */
    public function hydrateMany(array $originals)
    {
        return new Collection(array_map([$this, 'hydrate'], $originals));
    }
}
