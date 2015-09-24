<?php

namespace Luminous\Bridge;

interface HasParameter
{
    /**
     * Get an parameter from this instance.
     *
     * @param string $key
     * @return string
     */
    public function parameter($key);
}
