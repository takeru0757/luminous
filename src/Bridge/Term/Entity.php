<?php

namespace Luminous\Bridge\Term;

use stdClass;
use Illuminate\Support\Collection;
use Luminous\Bridge\Entity as BaseEntity;
use Luminous\Bridge\WP;

abstract class Entity extends BaseEntity
{
    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [
        'id' => 'term_id',
        'raw_description' => 'description',
    ];

    /**
     * Create a new term entity instance.
     *
     * @param \Luminous\Bridge\Term\Type $type
     * @param \stdClass $original
     * @return void
     */
    public function __construct(Type $type, stdClass $original)
    {
        parent::__construct($type, $original);
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
     * Get the description.
     *
     * @return string
     */
    protected function getDescriptionAttribute()
    {
        return $this->stripTags($this->raw_description);
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
