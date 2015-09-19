<?php

namespace Luminous\Bridge\Post\Parameters;

use InvalidArgumentException;

/**
 * The post parameter for the post query.
 *
 * @link https://codex.wordpress.org/Class_Reference/WP_Query#Post_.26_Page_Parameters
 */
trait PostParameter
{
    /**
     * The post where parameters for the query.
     *
     * @var array
     */
    protected $postWheres = [];

    /**
     * The post columns.
     *
     * @var array
     */
    protected $postColumns = [
        'id'        => ['in' => 'post__in', 'not in' => 'post__not_in'],
        'parent_id' => ['in' => 'post_parent__in', 'not in' => 'post_parent__not_in'],
        'slug'      => ['=' => 'name'],
        'path'      => ['=' => 'pagename'],
    ];

    /**
     * Add a post parameter to the query.
     *
     * @param string $column
     * @param string $operator
     * @param int|string|array $value
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function wherePost($column, $operator, $value = null)
    {
        if (! in_array($column, array_keys($this->postColumns))) {
            throw new InvalidArgumentException;
        }

        if (func_num_args() == 2) {
            list($operator, $value) = ['=', $operator];
        }

        if (in_array($column, ['id', 'parent_id'])) {
            $operator = in_array($operator, ['=', 'in']) ? 'in' : 'not in';
            $value = array_map('intval', (array) $value);
        }

        $this->postWheres[] = compact('column', 'operator', 'value');

        return $this;
    }

    /**
     * Get post parameter as WordPress query.
     *
     * @var array
     */
    protected function getPostQuery()
    {
        $query = [];

        foreach ($this->postWheres as $where) {
            $param = $this->postColumns[$where['column']][$where['operator']];
            $query[$param] = $where['value'];
        }

        return $query;
    }
}
