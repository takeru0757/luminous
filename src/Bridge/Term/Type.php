<?php

namespace Luminous\Bridge\Term;

use Luminous\Bridge\EntityAttributeTrait;

class Type
{
    use EntityAttributeTrait;

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [];

    /**
     * Create new term type (taxonomy) instance.
     *
     * @param \stdClass $original
     * @return void
     */
    public function __construct($original)
    {
        $this->original = $original;
    }
}
