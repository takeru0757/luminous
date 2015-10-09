<?php

namespace Luminous\Bridge\Post\Entities;

use Luminous\Bridge\Post\Entity as BaseEntity;

class HierarchicalEntity extends BaseEntity
{
    /**
     * Get the children.
     *
     * @return \Luminous\Bridge\Post\Query\Builder
     */
    protected function getChildrenAttribute()
    {
        return $this->wp->posts($this->type)->wherePost('parent_id', $this->id);
    }
}
