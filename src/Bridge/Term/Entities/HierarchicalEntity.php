<?php

namespace Luminous\Bridge\Term\Entities;

use Closure;
use Luminous\Bridge\Term\Entity as BaseEntity;

class HierarchicalEntity extends BaseEntity
{
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
        return $this->wp->terms($this->type)->whereTerm($column, $this->id);
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
        return $count + ($sum ? $this->children->get()->sum('count') : 0);
    }
}
