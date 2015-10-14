<?php

namespace Luminous\Bridge;

use InvalidArgumentException;

trait UrlPathTrait
{
    /**
     * Get the URL path.
     *
     * @param string $key
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function urlPath($key)
    {
        $value = (string) $this->{$key};

        if ($value === '') {
            throw new InvalidArgumentException("URL path for {$key} does not exist.");
        }

        return $value;
    }
}
