<?php

namespace Luminous\Bridge\Term\Query\Parameters;

use InvalidArgumentException;

/**
 * The ordering parameter for the term query.
 *
 * @link https://codex.wordpress.org/Function_Reference/get_terms
 */
trait OrderByParameter
{
    /**
     * The ordering parameter for the query.
     *
     * @var array
     */
    protected $orderBy = [
        'column'    => 'name',
        'direction' => 'asc',
    ];

    /**
     * Columns can be used by ordering.
     *
     * @var array
     */
    protected $orderByColumns = [
        'id'    => 'id',
        'count' => 'count',
        'name'  => 'name',
        'slug'  => 'slug',
    ];

    /**
     * Add an ordering parameter to the query.
     *
     * @param string $column
     * @param string $direction Possible values are 'asc' and 'desc'.
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function orderBy($column, $direction = 'asc')
    {
        if (! $column) {
            $this->orderBy = null;
            return $this;
        }

        if (! in_array($column, array_keys($this->orderByColumns))) {
            throw new InvalidArgumentException;
        }

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $this->orderBy = compact('column', 'direction');

        return $this;
    }

    /**
     * Get ordering parameter as WordPress query.
     *
     * @todo Use GMT columns.
     *
     * @var array
     */
    protected function getOrderByQuery()
    {
        if (! $this->orderBy) {
            return ['orderby' => 'none'];
        }

        return ['orderby' => $this->orderBy['column'], 'order' => strtoupper($this->orderBy['direction'])];
    }
}
