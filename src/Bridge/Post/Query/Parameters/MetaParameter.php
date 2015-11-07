<?php

namespace Luminous\Bridge\Post\Query\Parameters;

use InvalidArgumentException;

/**
 * The meta parameter for the post query.
 *
 * @todo Support 'OR' relation.
 *
 * @link https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters
 */
trait MetaParameter
{
    /**
     * The meta where parameters for the query.
     *
     * @var array
     */
    protected $metaWheres = [];

    /**
     * The available operators for the meta parameter.
     *
     * @var array
     */
    protected $metaOperators = [
        '=', '!=', '>', '>=', '<', '<=',
        'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS'
    ];

    /**
     * The available types for the meta parameter.
     *
     * @var array
     */
    protected $metaTypes = ['NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'];

    /**
     * Add a meta parameter to the query.
     *
     * @param string $column
     * @param string $operator
     * @param string|array $value
     * @param string $type
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereMeta($column, $operator, $value = null, $type = null)
    {
        if (func_num_args() === 2) {
            list($operator, $value) = ['=', $operator];
        }

        if (! in_array($operator, $this->metaOperators)) {
            throw new InvalidArgumentException;
        }

        if ($type && ! in_array($type, $this->metaTypes)) {
            throw new InvalidArgumentException;
        }

        $this->metaWheres[] = compact('column', 'operator', 'value', 'type');

        return $this;
    }

    /**
     * Get the meta parameter as WordPress query.
     *
     * @var array
     */
    protected function getMetaQuery()
    {
        $query = [];

        foreach ($this->metaWheres as $where) {
            $value = [
                'key'       => $where['column'],
                'value'     => $where['value'],
                'compare'   => $where['operator'],
            ];

            if ($where['type']) {
                $value['type'] = $where['type'];
            }

            $query[] = $value;
        }

        return $query ? ['meta_query' => $query] : [];
    }
}
