<?php

namespace Luminous\Bridge\Post\QueryTraits;

use Luminous\Bridge\Term\Type;
use Luminous\Bridge\Term\Entities\Entity;

/**
 * The term parameter for the post query.
 *
 * @todo Support 'OR' relation.
 *
 * @link https://codex.wordpress.org/Class_Reference/WP_Query#Taxonomy_Parameters
 */
trait TermWhereTrait
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
     * You can pass a term entity instance.
     * ```php
     * $query->whereTerm($term);
     * $query->whereTerm($term, ['include_children' => false]);
     * ```
     *
     * @param string|\Luminous\Bridge\Term\Type $column The term type (taxonomy).
     * @param string $operator Possible values are 'in', 'not in', 'and', 'exists' and 'not exists'.
     * @param int|string|\Luminous\Bridge\Term\Entities\Entity $value
     * @param array $options
     * @return $this
     */
    public function whereTerm($column, $operator = null, $value = null, $options = [])
    {
        if (func_num_args() === 1 && $column instanceof Entity) {
            list($column, $operator, $value, $options) = [$column->type, 'in', $column, $operator ?: []];
        } elseif (func_num_args() === 2) {
            list($operator, $value) = ['in', $operator];
        }

        if ($column instanceof Type) {
            $column = $column->name;
        }

        if ($value instanceof Entity) {
            $value = $value->id;
            $options['field'] = 'id';
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
                'field'    => $fields[$where['options']['field']],
                'include_children' => $where['options']['include_children'],
            ];
        }

        return $query ? ['tax_query' => $query] : [];
    }
}
