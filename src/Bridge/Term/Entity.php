<?php

namespace Luminous\Bridge\Term;

use stdClass;
use Illuminate\Support\Collection;
use Luminous\Bridge\WP;
use Luminous\Bridge\Entity as BaseEntity;

abstract class Entity extends BaseEntity
{
    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [
        'id' => 'term_id',
    ];

    /**
     * Create a new term entity instance.
     *
     * @param \Luminous\Bridge\WP $wp
     * @param \stdClass $original
     * @param \Luminous\Bridge\Term\Type $type
     * @return void
     */
    public function __construct(WP $wp, stdClass $original, Type $type)
    {
        parent::__construct($wp, $original, $type);
    }

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
            return $this->wp->term($id, $this->type->name);
        }, get_ancestors($this->original->term_id, $this->original->taxonomy, 'taxonomy'));

        return new Collection($ancestors);
    }

    /**
     * Get the path.
     *
     * @return string
     */
    protected function getPathAttribute()
    {
        $slugs = $this->ancestors->reverse()->pluck('slug')->push($this->slug);
        return implode('/', $slugs->all());
    }
}
