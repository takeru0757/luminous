<?php

namespace Luminous\Bridge;

use BadMethodCallException;
use Countable;
use IteratorAggregate;

/**
 * @property-read int|null $limit
 * @property-read int $offset
 */
abstract class QueryBuilder implements Countable, IteratorAggregate
{
    /**
     * The wp instance.
     *
     * @var \Luminous\Bridge\WP
     */
    protected $wp;

    /**
     * The entity builder instance.
     *
     * @var \Luminous\Bridge\Builder
     */
    protected $entityBuilder;

    /**
     * The maximum number of records to return.
     *
     * @var int|null
     */
    protected $limit;

    /**
     * The number of records to skip.
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * Dynamically retrieve attributes on the entity.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (in_array($key, ['limit', 'offset'])) {
            return $this->{$key};
        }
    }

    /**
     * Create a new query builder instance.
     *
     * @param \Luminous\Bridge\WP $wp
     * @param \Luminous\Bridge\Builder $entityBuilder
     * @return void
     */
    public function __construct(WP $wp, Builder $entityBuilder)
    {
        $this->wp = $wp;
        $this->entityBuilder = $entityBuilder;
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param int $value
     * @return \Luminous\Bridge\QueryBuilder|static
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param int|null $value
     * @return $this
     */
    public function limit($value)
    {
        if ($value && $value > 0) {
            $this->limit = $value;
        } else {
            $this->limit = null;
        }

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param int $value
     * @return \Luminous\Bridge\QueryBuilder|static
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param int $page
     * @param int $perPage
     * @return \Luminous\Bridge\QueryBuilder|static
     */
    public function forPage($page, $perPage = 10)
    {
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    /**
     * Execute the query.
     *
     * @return \Illuminate\Support\Collection
     */
    abstract public function get();

    /**
     * Execute the query and get the first result.
     *
     * @return mixed
     */
    public function first()
    {
        return $this->take(1)->get()->first();
    }

    /**
     * Implementation for Countable.
     *
     * @return int
     */
    abstract public function count();

    /**
     * Implementation for IteratorAggregate.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->get()->getIterator();
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $arguments)
    {
        if (method_exists($collection = $this->get(), $method)) {
            return call_user_func_array([$collection, $method], $arguments);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }
}
