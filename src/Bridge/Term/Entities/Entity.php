<?php

namespace Luminous\Bridge\Term\Entities;

use stdClass;
use ArrayAccess;
use Illuminate\Support\Collection;
use Luminous\Bridge\EntityAttributeTrait;
use Luminous\Bridge\EntityParameterTrait;
use Luminous\Bridge\HasArchive;
use Luminous\Bridge\HasParameter;
use Luminous\Bridge\Term\Type;
use Luminous\Bridge\WP;

abstract class Entity implements HasArchive, HasParameter, ArrayAccess
{
    use EntityAttributeTrait;
    use EntityParameterTrait;

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [
        'id' => 'term_id',
    ];

    /**
     * The term type (taxonomy) object.
     *
     * @var \Luminous\Bridge\Term\Type
     */
    public $type;

    /**
     * Create a new entity instance.
     *
     * @param \stdClass $original
     * @param \Luminous\Bridge\Term\Type $type
     * @return void
     */
    public function __construct(stdClass $original, Type $type)
    {
        $this->original = $original;
        $this->type = $type;
    }

    /**
     * Get the ancestors.
     *
     * @uses \get_ancestors()
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getAncestorsAttribute()
    {
        if ($this->type->hierarchical) {
            $ancestors = get_ancestors($this->id, $this->type->name, 'taxonomy');
        } else {
            $ancestors = [];
        }

        return new Collection(array_map(function ($id) {
            return WP::term($id, $this->type->name);
        }, $ancestors));
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

    /**
     * Get the route prefix for archive of this instance.
     *
     * @return string
     */
    public function getRoutePrefix()
    {
        return $this->type->name;
    }

    /**
     * Wheter this instance has archive.
     *
     * @return bool
     */
    public function hasArchive()
    {
        return $this->type->public;
    }
}
