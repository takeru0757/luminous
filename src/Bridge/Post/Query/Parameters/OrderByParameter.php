<?php

namespace Luminous\Bridge\Post\Query\Parameters;

use InvalidArgumentException;

/**
 * The ordering parameter for the post query.
 *
 * @todo Support 'post__in'.
 *
 * @link https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters
 */
trait OrderByParameter
{
    /**
     * The ordering parameter for the query.
     *
     * @var array
     */
    protected $orders = [];

    /**
     * The ordering parameter for the query.
     *
     * @var string
     */
    protected $orderMetaKey;

    /**
     * The ordering parameter for the query.
     *
     * @var string
     */
    protected $orderMetaType;

    /**
     * Columns can be used by ordering.
     *
     * @var array
     */
    protected $orderByColumns = [
        'rand'          => 'rand',
        'id'            => 'ID',
        'created_at'    => 'date',
        'modified_at'   => 'modified',
        'order'         => 'menu_order',
        'slug'          => 'name',
        'parent_id'     => 'parent',
        'comment_count' => 'comment_count',
        'meta_value'    => 'meta_value',
        'meta_value_num' => 'meta_value_num',
        //'post__in' => 'post__in',
    ];

    /**
     * Add an ordering parameter to the query.
     *
     * @param string $column
     * @param string $direction Possible values are 'asc' and 'desc'.
     * @param string $metaKey
     * @param string $metaType
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function orderBy($column, $direction = 'asc', $metaKey = null, $metaType = null)
    {
        if (in_array($column, ['meta_value', 'meta_value_num'])) {
            if (! $metaKey) {
                throw new InvalidArgumentException;
            }
            $this->orderMetaKey = $metaKey;
            $this->orderMetaType = $metaType;
        }

        if (! in_array($column, array_keys($this->orderByColumns))) {
            throw new InvalidArgumentException;
        }

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $this->orders[] = compact('column', 'direction');

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
        $query = [];

        foreach ($this->orders as $order) {
            $query[$this->orderByColumns[$order['column']]] = strtoupper($order['direction']);
        }

        $meta = [];

        if ($this->orderMetaKey) {
            $meta['meta_key'] = $this->orderMetaKey;
        }

        if ($this->orderMetaType) {
            $meta['meta_type'] = $this->orderMetaType;
        }

        return ['orderby' => $query ?: 'none' ] + $meta;
    }
}
