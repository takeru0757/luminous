<?php

namespace Luminous\Bridge;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function get($id)
    {
        try {
            $original = call_user_func_array([$this, 'getOriginal'], func_get_args());
        } catch (RecordNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        return $this->make($original);
    }

    /**
     * Get an original object.
     *
     * @param mixed $id
     * @return mixed
     *
     * @throws \Luminous\Bridge\Exceptions\RecordNotFoundException
     */
    abstract protected function getOriginal($id);

    /**
     * Hydrate an original object.
     *
     * @param mixed $original
     * @return \Luminous\Bridge\Entity
     *
     * @throws \Luminous\Bridge\Exceptions\MissingEntityException
     */
    abstract public function make($original);

    /**
     * Hydrate many original objects.
     *
     * @param array $originals
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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getType($name)
    {
        if (array_key_exists($name, $this->types)) {
            return $this->types[$name];
        }

        try {
            $original = $this->getOriginalType($name);
        } catch (TypeNotExistException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        return $this->types[$name] = $this->makeType($original);
    }

    /**
     * Get an original type object.
     *
     * @param string $name
     * @return mixed
     *
     * @throws \Luminous\Bridge\Exceptions\TypeNotExistException
     */
    abstract protected function getOriginalType($name);

    /**
     * Hydrate an original type object.
     *
     * @param mixed $original
     * @return \Luminous\Bridge\Type
     */
    abstract protected function makeType($original);
}
