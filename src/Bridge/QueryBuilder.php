<?php

namespace Luminous\Bridge;

abstract class QueryBuilder
{
    /**
     * The maximum number of records to return.
     *
     * @var int|null
     */
    public $limit;

    /**
     * The number of records to skip.
     *
     * @var int
     */
    public $offset = 0;

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
}
