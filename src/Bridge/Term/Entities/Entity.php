<?php

namespace Luminous\Bridge\Term\Entities;

use stdClass;
use ArrayAccess;
use Illuminate\Support\Collection;
use Luminous\Bridge\EntityAttributeTrait;
use Luminous\Bridge\EntityParameterTrait;
use Luminous\Bridge\Term\Builder;
use Luminous\Bridge\Term\Type;

abstract class Entity implements ArrayAccess
{
    use EntityAttributeTrait;
    use EntityParameterTrait;

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [
        'id' => 'term_id', // int
    ];

    /**
     * The term type (taxonomy) object.
     *
     * @var \Luminous\Bridge\Term\Type
     */
    protected $type;

    /**
     * Create a new entity instance.
     *
     * @param \stdClass $original
     * @return void
     */
    public function __construct(stdClass $original)
    {
        $this->original = $original;
        $this->type = Type::get($original->taxonomy);
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
            return Builder::get($id, $this->type->name);
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
}
