<?php

namespace Luminous\Bridge\Term\Entities;

use Illuminate\Support\Collection;
use Luminous\Bridge\WP;
use Luminous\Bridge\Term\Entity as BaseEntity;

class HierarchicalEntity extends BaseEntity
{
    /**
     * Get the ancestors.
     *
     * @uses \get_ancestors()
     *
     * @return \Illuminate\Support\Collection|\Luminous\Bridge\Term\Entity[]
     */
    protected function getAncestorsAttribute()
    {
        $ancestors = array_map(function ($id) {
            return WP::term($id, $this->type);
        }, get_ancestors($this->id, $this->original->taxonomy, 'taxonomy'));

        return new Collection($ancestors);
    }

    /**
     * Get the path.
     *
     * @return string
     */
    protected function getPathAttribute()
    {
        $path = parent::getPathAttribute();
        return $this->ancestors->pluck('slug')->reverse()->push($path)->implode('/');
    }

    /**
     * Get the children.
     *
     * @return \Luminous\Bridge\Term\Query\Builder
     */
    protected function getChildrenAttribute()
    {
        return $this->children();
    }

    /**
     * Get the children.
     *
     * @param bool $direct
     * @return \Luminous\Bridge\Term\Query\Builder
     */
    public function children($direct = true)
    {
        $column = $direct ? 'parent_id' : 'ancestor_id';
        return WP::terms($this->type)->whereTerm($column, $this->id);
    }

    /**
     * Get the count.
     *
     * @param bool $sum
     * @return int
     */
    public function count($sum = true)
    {
        $count = parent::count();
        return $count + ($sum ? $this->children->sum('count') : 0);
    }
}
