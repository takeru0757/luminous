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
     * @param \Luminous\Bridge\Term\Type $type
     * @param \stdClass $original
     * @return void
     */
    public function __construct(WP $wp, Type $type, stdClass $original)
    {
        parent::__construct($wp, $type, $original);
    }

    /**
     * Get the path.
     *
     * @return string
     */
    protected function getPathAttribute()
    {
        return $this->slug;
    }

    /**
     * Get the count.
     *
     * @return int
     */
    protected function getCountAttribute()
    {
        return $this->count();
    }

    /**
     * Get the count.
     *
     * @return int
     */
    public function count()
    {
        return intval($this->original->count);
    }
}
