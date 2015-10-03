<?php

namespace Luminous\Bridge\Post\Query\Parameters;

use DateTime;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * The date parameter for the post query.
 *
 * @todo Support Comparable value ('=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN').
 * @todo Support 'OR' relation.
 * @todo Support GMT colums.
 *
 * @link https://codex.wordpress.org/Class_Reference/WP_Query#Date_Parameters
 */
trait DateParameter
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
        'modified_at' => 'post_modified',
    ];

    /**
     * Add a date parameter to the query.
     *
     * Available operators: '=', '>', '>=', '<', '<='
     * Available value types:
     * - An array has keys: 'year', 'month', 'week', 'day', 'hour', 'minute' and 'second'.
     * - A DateTime instance
     *
     * @param string $column
     * @param string $operator
     * @param array|\DateTime $value
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereDate($column, $operator, $value = null)
    {
        if (! in_array($column, array_keys($this->dateWhereColumns))) {
            throw new InvalidArgumentException;
        }

        if (func_num_args() === 2) {
            list($operator, $value) = ['=', $operator];
        }

        if (! in_array($operator, ['=', '>', '>=', '<', '<='])) {
            throw new InvalidArgumentException;
        }

        if ($value instanceof DateTime) {
            $date = $value instanceof Carbon ? $value : Carbon::instance($value);
            $keys = ['year', 'month', 'day', 'hour', 'minute', 'second'];
            $value = array_combine($keys, array_map(function ($key) use ($date) {
                return $date->{$key};
            }, $keys));
        }

        $this->dateWheres[] = compact('column', 'operator', 'value');

        return $this;
    }

    /**
     * Get the date parameter as WordPress query.
     *
     * @var array
     */
    protected function getDateQuery()
    {
        $query = [];

        foreach ($this->dateWheres as $where) {
            switch ($operator = $where['operator']) {
                case '>':
                case '>=':
                    $value = ['after' => $where['value']];
                    $inclusive = $operator === '>=';
                    break;
                case '<':
                case '<=':
                    $value = ['before' => $where['value']];
                    $inclusive = $operator === '<=';
                    break;
                default:
                    list($value, $inclusive) = [$where['value'], false];
                    break;
            }

            $query[] = array_merge([
                'column' => $this->dateWhereColumns[$where['column']],
                'inclusive' => $inclusive,
            ], $value);
        }

        return $query ? ['date_query' => $query] : [];
    }
}
