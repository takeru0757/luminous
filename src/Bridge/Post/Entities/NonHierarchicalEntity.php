<?php

namespace Luminous\Bridge\Post\Entities;

use Luminous\Bridge\WP;
use Luminous\Bridge\Post\Entity as BaseEntity;

/**
 * @property-read \Luminous\Bridge\Post\Entity|null $newer The newer post.
 * @property-read \Luminous\Bridge\Post\Entity|null $older The older post.
 */
class NonHierarchicalEntity extends BaseEntity
{
    /**
     * Get the newer post.
     *
     * @return \Luminous\Bridge\Post\Entity|null
     */
    public function getNewerAttribute()
    {
        return WP::posts($this->type)
            ->whereDate('created_at', '>', $this->created_at)
            ->orderBy('created_at', 'asc')
            ->first();
    }

    /**
     * Get the older post.
     *
     * @return \Luminous\Bridge\Post\Entity|null
     */
    public function getOlderAttribute()
    {
        return WP::posts($this->type)
            ->whereDate('created_at', '<', $this->created_at)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
