<?php

namespace Luminous\Bridge\Term\Query\Parameters;

use InvalidArgumentException;

/**
 * The term parameter for the term query.
 *
 * @link https://codex.wordpress.org/Function_Reference/get_terms
 */
trait TermParameter
{
    /**
     * The term where parameters for the query.
     *
     * @var array
     */
    protected $termWheres = [];

    /**
     * Whether includes empty terms.
     *
     * @var bool
     */
    protected $includeEmpty = true;

    /**
     * The columns for post where.
     *
     * @var array
     */
    protected $termWhereColumns = [
        'id'            => ['in' => 'include', 'not in' => 'exclude'],
        'ancestor_id'   => ['=' => 'child_of', 'not in' => 'exclude_tree'],
        'parent_id'     => ['=' => 'parent'],
        'slug'          => ['=' => 'slug'],
    ];

    /**
     * Set whether includes empty terms.
     *
     * @param bool $value
     * @return $this
     */
    public function includeEmpty($value)
    {
        $this->includeEmpty = $value;

        return $this;
    }

    /**
     * Set to get only root entities.
     *
     * @return $this
     */
    public function root()
    {
        $this->whereTerm('parent_id', 0);

        return $this;
    }

    /**
     * Add a term parameter to the query.
     *
     * @param string $column
     * @param string $operator
     * @param int|string|array $value
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function whereTerm($column, $operator, $value = null)
    {
        if (! in_array($column, array_keys($this->termWhereColumns))) {
            throw new InvalidArgumentException;
        }

        if (func_num_args() === 2) {
            list($operator, $value) = ['=', $operator];
        }

        if ($column === 'id') {
            $operator = in_array($operator, ['=', 'in']) ? 'in' : 'not in';
            $value = (array) $value;
        }

        if ($column === 'ancestor_id' && $operator === '!=') {
            $operator = 'not in';
            $value = (array) $value;
        }

        if ($column === 'slug') {
            $slugs = explode('/', $value);
            $value = last($slugs);
        }

        $this->termWheres[] = compact('column', 'operator', 'value');

        return $this;
    }

    /**
     * Get term parameter as WordPress query.
     *
     * @var array
     */
    protected function getTermQuery()
    {
        $query = [
            'hide_empty' => ! $this->includeEmpty,
        ];

        foreach ($this->termWheres as $where) {
            $param = $this->termWhereColumns[$where['column']][$where['operator']];
            $query[$param] = $where['value'];
        }

        return $query;
    }
}
