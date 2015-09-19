<?php

namespace Luminous\Bridge\Post\Parameters;

/**
 * The term parameter for the post query.
 *
 * @todo Support 'OR' relation.
 *
 * @link https://codex.wordpress.org/Class_Reference/WP_Query#Taxonomy_Parameters
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
     * Add a term parameter to the query.
     *
     * The option array accepts:
     * - field: (string) 'slug' or 'id'. Default value is 'slug'.
     * - include_children: (bool) Defaults to true.
     *
     * @param string $column The term type name (taxonomy).
     * @param string $operator Possible values are 'in', 'not in', 'and', 'exists' and 'not exists'.
     * @param string|array $value
     * @param array $options
     * @return $this
     */
    public function whereTerm($column, $operator, $value = null, $options = [])
    {
        if (func_num_args() == 2) {
            list($operator, $value) = ['in', $operator];
        }

        $options = array_merge([
            'field' => 'slug',
            'include_children' => true,
        ], $options);

        $this->termWheres[] = compact('column', 'operator', 'value', 'options');

        return $this;
    }

    /**
     * Get term parameter as WordPress query.
     *
     * @var array
     */
    protected function getTermQuery()
    {
        $query = [];

        $fields = [
            'id'    => 'term_id',
            'slug'  => 'slug',
        ];

        foreach ($this->termWheres as $where) {
            $query[] = [
                'taxonomy' => $where['column'],
                'terms'    => $where['value'],
                'operator' => strtoupper($where['operator']),
                'field'    => $fields[$where['options']['filed']],
                'include_children' => $where['options']['include_children'],
            ];
        }

        return $query ? ['tax_query' => $query] : [];
    }
}
