<?php

namespace Luminous\Bridge\Post\Query\Parameters;

use InvalidArgumentException;
use Luminous\Bridge\Post\Type;

/**
 * The post parameter for the post query.
 *
 * @link https://codex.wordpress.org/Class_Reference/WP_Query#Post_.26_Page_Parameters
 */
trait PostParameter
{
    /**
     * The post type for the query.
     *
     * @var string|string[]
     */
    protected $type = 'post';

    /**
     * The post status for the query.
     *
     * @var string|string[]
     */
    protected $status = 'publish';

    /**
     * Whether move sticky posts to the start of the set.
     *
     * @var bool
     */
    protected $stickyPosts = false;

    /**
     * The post where parameters for the query.
     *
     * @var array
     */
    protected $postWheres = [];

    /**
     * The columns for post where.
     *
     * @var array
     */
    protected $postWhereColumns = [
        'id'        => ['=' => 'p', 'in' => 'post__in', 'not in' => 'post__not_in'],
        'parent_id' => ['=' => 'post_parent', 'in' => 'post_parent__in', 'not in' => 'post_parent__not_in'],
        'path'      => ['=' => 'pagename'],
        'slug'      => ['=' => 'name'],
    ];

    /**
     * Set the post type of the query.
     *
     * @param \Luminous\Bridge\Post\Type|string|array $value
     * @return $this
     */
    public function type($value)
    {
        if ($value instanceof Type) {
            $this->type = $value->name;
        } else {
            $this->type = $value;
        }

        return $this;
    }

    /**
     * Set the status of the query.
     *
     * @param string|array $value
     * @return $this
     */
    public function status($value)
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Set whether move sticky posts to the start of the set.
     *
     * @param bool $value
     * @return $this
     */
    public function stickyPosts($value)
    {
        $this->stickyPosts = $value;

        return $this;
    }

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
        if (! in_array($column, array_keys($this->postWhereColumns))) {
            throw new InvalidArgumentException;
        }

        if (func_num_args() === 2) {
            list($operator, $value) = ['=', $operator];
        }

        if (in_array($column, ['id', 'parent_id']) && $operator !== '=') {
            $operator = $operator === 'in' ? 'in' : 'not in';
            $value = array_map('intval', (array) $value);
        }

        if ($column === 'path' && strpos($value, '/') === false) {
            $column = 'slug';
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
        $query = [
            'post_type' => $this->type,
            'post_status' => $this->status,
            'ignore_sticky_posts' => !$this->stickyPosts,
        ];

        foreach ($this->postWheres as $where) {
            $param = $this->postWhereColumns[$where['column']][$where['operator']];
            $query[$param] = $where['value'];
        }

        return $query;
    }
}
