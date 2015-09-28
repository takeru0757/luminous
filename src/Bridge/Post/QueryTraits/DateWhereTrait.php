<?php

namespace Luminous\Bridge\Post\QueryTraits;

use InvalidArgumentException;

/**
 * The date parameter for the post query.
 *
 * @todo Support Comparable value ('=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN').
 * @todo Support 'before' amd 'after'.
 * @todo Support 'OR' relation.
 * @todo Support GMT colums.
 *
 * @link https://codex.wordpress.org/Class_Reference/WP_Query#Date_Parameters
 */
trait DateWhereTrait
{
    /**
     * The date where parameters for the query.
     *
     * @var array
     */
    protected $dateWheres = [];

    /**
     * The columns for date where.
     *
     * @var array
     */
    protected $dateWhereColumns = [
        'created_at' => 'post_date',
        'updated_at' => 'post_modified',
    ];

    /**
     * Add a date parameter to the query.
     *
     * @param string $column
     * @param array $value An array of date values ('year', 'month', 'week', 'day', 'hour', 'minute' and 'second').
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereDateAt($column, $value = null)
    {
        if (! in_array($column, array_keys($this->dateWhereColumns))) {
            throw new InvalidArgumentException;
        }

        $type = 'at';

        $this->dateWheres[] = compact('type', 'column', 'value');

        return $this;
    }

    /**
     * Get date parameter as WordPress query.
     *
     * @var array
     */
    protected function getDateQuery()
    {
        $query = [];

        foreach ($this->dateWheres as $where) {
            $column = $this->dateWhereColumns[$where['column']];
            switch ($where['type']) {
                case 'at':
                    $query[] = array_merge(compact('column'), $where['value']);
                    break;
            }
        }

        return $query ? ['date_query' => $query] : [];
    }
}
