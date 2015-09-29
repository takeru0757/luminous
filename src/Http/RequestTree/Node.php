<?php

namespace Luminous\Http\RequestTree;

use Illuminate\Support\Fluent;

class Node extends Fluent
{
    /**
     * Create a new node.
     *
     * @param string $label
     * @param string $url
     * @param mixed $original
     * @return void
     */
    public function __construct($label, $url, $original = null)
    {
        parent::__construct(compact('label', 'url', 'original'));
    }
}
